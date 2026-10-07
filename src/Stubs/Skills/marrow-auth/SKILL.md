---
name: marrow-auth
description: Add or extend authentication/authorization in this Marrow app — login flow, route protection, and ability checks — using the framework's real primitives instead of hand-rolled session/password code. Use when asked to add login, protect a route, add roles/permissions, or gate an action behind a permission check.
---

# Marrow authentication & authorization

## First: is `marrow/warden` installed?

Check `composer.json`. If yes, and `modules/Auth/` doesn't exist yet:
```bash
php forge warden:install
php forge migrate
```
This publishes a **complete, plain, fully-editable** login/register/
password-reset/email-verification/2FA-challenge flow into
`modules/Auth/` — controllers, declarative `Forms/`, views, migrations,
routes. It's real application code from that point on, not a package
black box — read/edit it directly rather than asking "how do I customize
warden's login controller", there's no abstraction layer to work around.
Do not re-run `warden:install` expecting it to pick up a manual edit —
it never re-touches a published file (only `--force` overwrites, and
that discards your edits).

If no `marrow/warden`, build the flow by hand against the primitives
below — `Marrow\Auth\*` provides the building blocks (hashing, guards,
RBAC, 2FA secrets) but ships no actual login controller/views, deliberately.

## Protecting a route

```php
$router->get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth');                    // 401 (JSON) or redirect (web)

$router->get('/login', [LoginController::class, 'show'])
    ->middleware('guest');                    // inverse — already-authed users redirected away
```
`auth`'s redirect target is `config('auth.redirects.login')`, not
hardcoded — set it there if `/login` isn't the right path.

## Checking the current user

```php
$auth = app(\Marrow\Auth\AuthManager::class);   // or constructor-inject AuthManager
$auth->check();           // bool
$auth->user();             // current user model, or null
$auth->attempt($credentials);  // ['email' => ..., 'password' => ...]
$auth->login($user);
$auth->logout();
```

## Authorization — Gate/Policy, not inline role checks

Don't write `if ($user->role === 'admin')` scattered through controllers.
Define a Policy and check through the Gate:
```php
php forge make:policy PostPolicy --model=Post
```
```php
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->author_id || $user->hasRole('admin');
    }
}
```
In a controller (extends `Marrow\Http\Controller`):
```php
$this->authorize('update', $post);        // throws 403 on denial
if ($this->can('update', $post)) { ... }   // bool, no throw
```
For a policy not resolved by the model's own convention, target it
explicitly: `$this->bouncer(PostPolicy::class)->authorize('update', $post);`

## RBAC (roles/permissions)

If the app's `User` model uses the framework's RBAC tables
(`roles`, `permissions`, `role_permissions`, `user_roles`) — already
present if scaffolded from `marrow/skeleton` — assign/check with the
model's own RBAC methods (`hasRole()`, `hasPermission()`, `assignRole()`)
rather than querying the pivot tables directly.

## Two-factor

`HasTwoFactor` (a trait on `User`) provides the TOTP secret
encryption/verification primitives — but **nothing calls
`verifyTwoFactor()` automatically at login**. A user with 2FA enabled is
not actually protected unless something bridges that gap (`marrow/warden`'s
`TwoFactorChallengeController` is exactly that bridge — another reason to
prefer it over reimplementing the same bridge by hand).

## Verify

- An unauthenticated request to an `auth`-gated route redirects (web) or
  gets 401 (JSON `Accept`/`X-Requested-With`), not a 500.
- `$this->authorize(...)` denial renders a 403, not an uncaught exception.
- If 2FA is enabled for a test user, confirm the challenge is actually
  enforced end-to-end, not just that the secret can be generated/verified
  in isolation.
