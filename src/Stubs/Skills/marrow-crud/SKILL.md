---
name: marrow-crud
description: Build a complete CRUD resource in this Marrow app — migration, model, declarative form, controller, routes — following the framework's own conventions instead of ad hoc validation/response patterns. Use when asked to add a resource, entity, or "CRUD for X" (e.g. "add a Posts resource", "CRUD for products").
---

# Marrow CRUD resource

Build each piece in this order — later steps depend on earlier ones.

## 1. Migration

```bash
php forge make:migration create_{plural}_table --module={Module}
```
Define the schema in the generated `up()`. Run it with `php forge migrate`
once written (`--seed` too if a seeder exists).

## 2. Model

```bash
php forge make:model {Name} --module={Module}
```
Prefer class-level attributes over hand-written `$table`/`$fillable`
arrays:
```php
#[Table('{plural}')]
#[Column('title')]
#[Column('body')]
class {Name} extends Model
{
}
```
**`#[Column]` is always class-level, never on a declared property** — a
`Model`'s fields are virtual (`__get()`/`__set()`-backed); a real declared
property with the same name would shadow that storage entirely instead of
exposing it.

## 3. Form (validation)

Only if `marrow/form-builder` is installed (check `composer.json`) — if
not, use a plain `FormRequest` instead (`php forge make:form-request
Store{Name}Request --module={Module}`, `rules(): array`).

```bash
php forge make:form {Name}Form --module={Module}   # if the command exists; otherwise create by hand
```
```php
class {Name}Form extends Form
{
    #[CharField(label: 'Title', rules: 'required|string|max:255')]
    public string $title;

    #[TextareaField(label: 'Body', rules: 'required|string', rows: 6)]
    public string $body;
}
```
Available field attributes: `CharField`, `EmailField`, `PasswordField`,
`IntegerField`, `TextareaField`, `ChoiceField` (needs `choices: [...]`),
`BooleanField`, `HiddenField`. In the controller:
```php
$form = {Name}Form::fromRequest($request);
if (!$form->isValid()) {
    return $this->view('@{module}/{plural}/create', ['form' => $form]);
}
{Name}::create($form->cleanedData());
```
If `marrow/ui` is also installed, render it with a single
`<mui-form :form="form">...</mui-form>` tag instead of looping over fields
by hand — see the `marrow-ui-component` skill.

## 4. Controller

```bash
php forge make:controller {Name}Controller --module={Module}
```
Extend `Marrow\Http\Controller` (web) or `Marrow\Http\ApiController`
(JSON-first — gives `ok()`/`created()`/`notFound()`/`paginate()`/... instead
of hand-building response envelopes).

**Return type**: a method that can return either a redirect or a rendered
view declares `: Marrow\Http\Response` — `RedirectResponse`/`JsonResponse`
are genuine subtypes of it (marrow/framework >= 3.0), never type against
`Symfony\Component\HttpFoundation\Response`.

```php
class {Name}Controller extends Controller
{
    public function index(): Response
    {
        return $this->view('@{module}/{plural}/index', ['items' => {Name}::all()]);
    }

    public function store(Request $request): Response
    {
        $form = {Name}Form::fromRequest($request);
        if (!$form->isValid()) {
            return $this->view('@{module}/{plural}/create', ['form' => $form]);
        }
        {Name}::create($form->cleanedData());
        return $this->redirectToRoute('{plural}.index');
    }
}
```

## 5. Routes

In `modules/{Module}/routes.php`:
```php
$router->get('/{plural}', [{Name}Controller::class, 'index'])->name('{plural}.index');
$router->get('/{plural}/create', [{Name}Controller::class, 'create'])->name('{plural}.create');
$router->post('/{plural}', [{Name}Controller::class, 'store'])->name('{plural}.store');
$router->get('/{plural}/{id}/edit', [{Name}Controller::class, 'edit'])->name('{plural}.edit');
$router->put('/{plural}/{id}', [{Name}Controller::class, 'update'])->name('{plural}.update');
$router->delete('/{plural}/{id}', [{Name}Controller::class, 'destroy'])->name('{plural}.destroy');
```
Gate write routes behind auth where appropriate:
`->middleware('auth')` on the group, or `protected array $middleware =
['auth'];` on the controller itself.

## Verify

- `php forge route:list` shows all six routes with the right names.
- `php forge migrate:status` shows the new migration applied.
- Submit the create form with invalid data first — confirm validation
  errors render inline (via the `Form`'s own `errors`), not a generic 500.
