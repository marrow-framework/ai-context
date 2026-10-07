---
name: marrow-service-integration
description: Integrate a third-party service (payment provider, external API, webhook sender) into this Marrow app using the framework's own primitives instead of a hand-rolled HTTP client wrapper and raw hash_hmac/hash_equals webhook verification. Use when asked to integrate Stripe, an external API, or handle an inbound webhook.
---

# Marrow external service integration

marrow/framework ships three primitives for exactly this (see
`docs/integrations.md` in the installed framework's own `docs/`) — reach
for them before writing a bespoke HTTP client class or verifying a webhook
signature by hand.

## 1. Credentials live in `config/services.php`

```php
// config/services.php
'stripe' => [
    'base_uri'            => 'https://api.stripe.com/v1/',
    'secret'              => env('STRIPE_SECRET'),
    'webhook_secret'      => env('STRIPE_WEBHOOK_SECRET'),
    'webhook_header'      => 'Stripe-Signature',
    'webhook_timestamped' => true,   // this provider signs "{timestamp}.{payload}"
],
```

## 2. One class per service — `Support\ServiceIntegration`

```php
use Marrow\Support\ServiceIntegration;

class StripeIntegration extends ServiceIntegration
{
    protected static function key(): string
    {
        return 'stripe';   // reads config('services.stripe')
    }

    public function createCharge(array $payload): array
    {
        // http() already carries base_uri + a Bearer token from config('services.stripe.secret')
        return $this->http()->post('charges', ['json' => $payload])->json();
    }

    public function handleWebhook(Request $request): void
    {
        // Reads webhook_header/webhook_timestamped/webhook_tolerance straight
        // from config('services.stripe') — no need to repeat them here.
        if (!$this->verifyWebhook($request)) {
            abort(400, 'Invalid signature.');
        }
        // ... process $request->getContent()
    }
}
```
Bind it from the owning module's `register()` — plain reflection
autowiring resolves the constructor (`HttpClient`, `ConfigRepository`),
nothing extra to wire up:
```php
$this->container->singleton(StripeIntegration::class, StripeIntegration::class);
```
Override `baseUri()` directly instead of relying on config when a
provider's host isn't meant to be app-configurable. Override
`tokenConfigKey()` if the provider's credential isn't named `secret` in
its config block.

## 3. A route-level alternative: the `webhook` middleware

For a route that doesn't need a full `ServiceIntegration` class:
```php
$router->post('/webhooks/stripe', [WebhookController::class, 'handle'])
    ->middleware('webhook:stripe');
```
Reads the same `config('services.stripe')` block — the route declaration
itself never carries a literal secret. Responds `400` (not 401/403 — an
invalid signature here almost always means a misconfigured secret, not a
credentialed-but-unauthorized caller) if the secret is unset, the header
is missing, or the signature doesn't match.

**Also exempt the route from CSRF** (`config/shield.php` →
`'csrf_except' => ['webhooks/*']`) — it's a cross-origin POST by
definition, and `webhook` already verifies authenticity its own way.

## 4. Raw signature verification, if neither fits

```php
use Marrow\Http\Webhook\WebhookSignature;

WebhookSignature::check($request->getContent(), $header, $secret);                 // single-value header
WebhookSignature::checkTimestamped($header, $request->getContent(), $secret, 300); // "t=...,v1=..." scheme
```
**Always verify against the raw body** (`$request->getContent()`) — never
a re-encoded `json_encode($request->request->all())`, which can differ
byte-for-byte from what the provider actually signed and silently break
verification for a genuine request.

## Verify

- A webhook request with a tampered body or wrong signature gets `400`,
  not processed.
- A webhook request with a *valid* signature from the real provider
  (use their CLI/dashboard test-send feature) is actually processed.
- Credentials never appear in a route definition, a log line, or a
  committed file — only in `config/services.php` reading from `env()`.
