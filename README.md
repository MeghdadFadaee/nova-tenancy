# Nova Tenancy

[![Latest Version on Packagist](https://img.shields.io/packagist/v/meghdadfadaee/nova-tenancy.svg?style=flat-square)](https://packagist.org/packages/meghdadfadaee/nova-tenancy)
[![Total Downloads](https://img.shields.io/packagist/dt/meghdadfadaee/nova-tenancy.svg?style=flat-square)](https://packagist.org/packages/meghdadfadaee/nova-tenancy)
[![License](https://img.shields.io/packagist/l/meghdadfadaee/nova-tenancy.svg?style=flat-square)](LICENSE.md)

A native Laravel Nova 5 tenant selector, request-scoped tenant context, and dynamic tenant branding. It does not provide database tenancy and does not depend on an HTML card, logo, or third-party tenancy package.

## Features

- Tenant selection without leaving Nova's SPA lifecycle.
- Dynamic header title and logo for the selected tenant.
- Encrypted cookie persistence with authorization checked on every resolution.
- Server-side tenant search and pagination.
- Request-scoped context plus `CurrentTenantId` and `CurrentTenantModel` bindings.
- Opt-in tenant-aware Nova resource scoping and assignment.
- Dark mode, responsive layout, keyboard focus, reduced motion, and RTL support.
- No database migrations and no dependency on a full tenancy framework.

## Requirements

- PHP 8.1+
- Laravel 10–13
- Laravel Nova 5.8+

## Installation

This package requires an existing licensed Nova installation. Configure Composer authentication for Nova through Nova's documented mechanism; never commit `auth.json` or credentials. Then install the package:

```bash
composer require meghdadfadaee/nova-tenancy
php artisan vendor:publish --tag=nova-tenancy-config
```

The package auto-registers its Nova tool. Its normal sidebar item is disabled by default because the selector is intended to live in the user menu.

No migrations are required.

## Configure a tenant model

Implement `NovaTenant` on the model that represents a tenant. The included trait maps common Eloquent attributes and can be configured in `config/nova-tenancy.php`.

```php
use Illuminate\Database\Eloquent\Model;
use MeghdadFadaee\NovaTenancy\Concerns\HasNovaTenantPresentation;
use MeghdadFadaee\NovaTenancy\Contracts\NovaTenant;

class Team extends Model implements NovaTenant
{
    use HasNovaTenantPresentation;

    public function novaTenantLogoUrl(): ?string
    {
        return $this->pic_url;
    }
}
```

The contract exposes:

```php
public function novaTenantKey(): int|string;
public function novaTenantTitle(): string;
public function novaTenantLogoUrl(): ?string;

/** @return array{description?: string|null, badge?: string|null, accent?: string|null} */
public function novaTenantMeta(): array;
```

Accent values are accepted only as six-digit hex colors before they reach the frontend.

## Define tenant access

The default provider asks the authenticated user for an Eloquent query. That query is the authorization boundary for the list, search, and selection endpoints.

```php
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use MeghdadFadaee\NovaTenancy\Contracts\ProvidesNovaTenants;

class User extends Authenticatable implements ProvidesNovaTenants
{
    public function novaTenantQuery(Request $request): Builder
    {
        return $this->teams()
            ->where('visible', true)
            ->orderBy('priority');
    }
}
```

For more control, extend `EloquentTenantProvider` (or implement `TenantProvider`) and set its class as `provider` in the package configuration. Custom providers control access, searching, ordering, and key resolution while still returning Eloquent models implementing `NovaTenant`. The base provider supplies safe default `search()` and `find()` implementations derived from the same authorized query.

## Add the user-menu link

Nova supports a single user-menu callback, so the package provides an item that safely composes with the application's existing items:

```php
use Illuminate\Http\Request;
use Laravel\Nova\Menu\Menu;
use Laravel\Nova\Nova;
use MeghdadFadaee\NovaTenancy\NovaTenancy;

Nova::userMenu(function (Request $request, Menu $menu): Menu {
    return $menu->append(NovaTenancy::userMenuItem());
});
```

The sidebar link can instead be enabled with `menu.sidebar`.

## Resolve the current tenant

The context and convenience aliases are scoped once per Laravel request or worker lifecycle:

```php
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\NovaTenancy;

$context = app(CurrentTenantContext::class);

$context->id();             // int|string|null
$context->model();          // NovaTenant model|null
$context->requireId();      // throws TenantNotSelected
$context->requireModel();   // throws TenantNotSelected

app('CurrentTenantId');
app('CurrentTenantModel');

NovaTenancy::id();
NovaTenancy::tenant();
```

For explicitly propagated jobs or commands, `run()` activates a tenant only for the callback and restores the previous context:

```php
$context->run($team, fn () => $service->generateReport());
```

The package does not automatically propagate tenants into queues, change database connections, scope application models, or partition caches and filesystems.

## Tenant-aware Nova resources

Extend `TenantResource` when a Nova resource must always use the selected tenant:

```php
use MeghdadFadaee\NovaTenancy\Nova\TenantResource;

class Invoice extends TenantResource
{
    public static $model = \App\Models\Invoice::class;

    protected static function tenantColumn(): string
    {
        return 'team_id';
    }

    protected static function afterTenantScope(
        NovaRequest $request,
        Builder $query,
        string $operation,
    ): Builder {
        return $query->latest();
    }
}
```

The base class scopes index, detail, edit, replicate, relatable, and Scout queries. It also forces the tenant column during creation and update. Its security-sensitive entry points are final; use the provided hooks to customize them.

For lenses, metrics, actions, or resources with another required base class, use `InteractsWithCurrentTenant` explicitly:

```php
return static::scopeToCurrentTenant($query, 'team_id');
```

`RequireTenant` is available for custom routes that must not run without a selection.

## SPA switching and branding

Selection uses Nova's authenticated request client, stores the authorized tenant key in an encrypted HTTP-only cookie, broadcasts `nova-tenancy:changed`, and navigates with `Nova.visit()`. The page is not reloaded.

The package overrides Nova's `AppLogo` component with the selected tenant's logo and title. The image source remains the fixed authenticated endpoint `/nova-vendor/nova-tenancy/current/logo`; remote images are redirected to rather than fetched by the server. Only root-relative, HTTP, and HTTPS logo URLs are accepted by the default response and tenant list payload.

If another package also replaces `AppLogo`, the last component registered by Nova wins.

## Customization

The published config controls cookie attributes, routes, presentation fields, search size/debounce, branding, menu placement, the default resource column, and the post-selection landing path.

Runtime callbacks are available for application logic that cannot live in cached config:

```php
NovaTenancy::authorizeUsing(
    fn (Request $request): bool => $request->user()->can('select-tenants')
);

NovaTenancy::landingUsing(
    fn (Request $request, Model $tenant): string => '/dashboards/main'
);

NovaTenancy::logoResponseUsing(
    fn (Request $request, Model $tenant): Response => Storage::disk('private')
        ->response($tenant->logo_path)
);
```

`TenantSelected` and `TenantCleared` events are dispatched after changes. English and Persian translations can be published with:

```bash
php artisan vendor:publish --tag=nova-tenancy-translations
```

## Development

```bash
composer test
npm test
npm run production
vendor/bin/pint
```

The tests use Orchestra Testbench. Production Nova assets in `dist/` are part of package releases so consuming applications do not need Node.js.

Please see [CONTRIBUTING.md](CONTRIBUTING.md) before submitting a change. Security issues should be reported according to [SECURITY.md](SECURITY.md), not through a public issue.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for release history.

## License

Nova Tenancy is open-sourced software licensed under the [MIT license](LICENSE.md).
