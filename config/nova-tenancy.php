<?php

use MeghdadFadaee\NovaTenancy\Providers\UserTenantProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Provider
    |--------------------------------------------------------------------------
    |
    | The provider is responsible for returning the authenticated user's
    | authorized tenant query, applying searches, and resolving a selected
    | tenant. You may replace this with any TenantProvider implementation.
    |
    */

    'provider' => UserTenantProvider::class,

    /*
    |--------------------------------------------------------------------------
    | Selection Cookie
    |--------------------------------------------------------------------------
    |
    | Nova Tenancy stores only the selected tenant key in this encrypted,
    | HTTP-only cookie. A null secure value follows the current request's
    | security, while the default lifetime remembers a selection for a year.
    |
    */

    'cookie' => [
        'name' => 'nova_tenant',
        'minutes' => 60 * 24 * 365,
        'path' => '/',
        'domain' => null,
        'secure' => null,
        'http_only' => true,
        'same_site' => 'lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | The URI is relative to Nova's configured path. The API prefix is an
    | application-relative path protected by Nova's authentication and the
    | package authorization middleware. Avoid trailing slashes in both.
    |
    */

    'routes' => [
        'uri' => 'nova-tenancy',
        'api_prefix' => 'nova-vendor/nova-tenancy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Selection
    |--------------------------------------------------------------------------
    |
    | These values control server pagination, search debounce in milliseconds,
    | and the Nova-relative SPA destination used after a tenant is selected.
    | A landingUsing callback may be registered for dynamic destinations.
    |
    */

    'selection' => [
        'per_page' => 12,
        'max_per_page' => 48,
        'search_debounce' => 300,
        'landing_path' => '/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Model Presentation
    |--------------------------------------------------------------------------
    |
    | These columns are used by HasNovaTenantPresentation and the default
    | provider. Leave optional presentation columns null when they are not
    | available. Individual contract methods may be overridden on the model.
    |
    */

    'model' => [
        'key_column' => null,
        'title_attribute' => 'name',
        'logo_attribute' => 'logo_url',
        'description_attribute' => null,
        'badge_attribute' => null,
        'accent_attribute' => null,
        'search_columns' => ['name'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nova Branding
    |--------------------------------------------------------------------------
    |
    | When enabled, Nova's header logo is replaced by the current tenant's
    | image and title. The link may be "selector", "home", or a Nova-relative
    | path. The fallback logo may be a root-relative, HTTP, or HTTPS URL.
    |
    */

    'branding' => [
        'enabled' => true,
        'show_logo' => true,
        'show_title' => true,
        'link' => 'selector',
        'fallback_logo' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation Menu
    |--------------------------------------------------------------------------
    |
    | A null label uses the package translation. The user-menu link is added
    | with NovaTenancy::userMenuItem(). Enable sidebar to also expose the
    | selector as a normal Nova tool menu section.
    |
    */

    'menu' => [
        'label' => null,
        'icon' => 'building-office-2',
        'sidebar' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant-Aware Nova Resources
    |--------------------------------------------------------------------------
    |
    | This is the default foreign-key column used by TenantResource and its
    | query helpers. A resource may override tenantColumn when its schema uses
    | another name, such as team_id or app_id.
    |
    */

    'resources' => [
        'tenant_column' => 'tenant_id',
    ],
];
