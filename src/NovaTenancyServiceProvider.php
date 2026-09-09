<?php

namespace MeghdadFadaee\NovaTenancy;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\Contracts\TenantProvider;
use MeghdadFadaee\NovaTenancy\Http\Middleware\Authorize;
use MeghdadFadaee\NovaTenancy\Support\TenantPayload;

class NovaTenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nova-tenancy.php', 'nova-tenancy');

        $this->app->scoped(TenantProvider::class, function ($app): TenantProvider {
            $provider = $app->make((string) config('nova-tenancy.provider'));

            if (! $provider instanceof TenantProvider) {
                throw new InvalidArgumentException('The configured Nova tenancy provider must implement '.TenantProvider::class.'.');
            }

            return $provider;
        });

        $this->app->scoped(CurrentTenant::class, fn ($app): CurrentTenant => new CurrentTenant(
            $app->make(TenantProvider::class),
            $app->make('request'),
        ));
        $this->app->alias(CurrentTenant::class, CurrentTenantContext::class);

        $this->app->scoped('CurrentTenantId', fn ($app): int|string|null => $app->make(CurrentTenantContext::class)->id());
        $this->app->scoped('CurrentTenantModel', fn ($app) => $app->make(CurrentTenantContext::class)->model());
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'nova-tenancy');

        $this->publishes([
            __DIR__.'/../config/nova-tenancy.php' => config_path('nova-tenancy.php'),
        ], 'nova-tenancy-config');
        $this->publishes([
            __DIR__.'/../lang' => lang_path('vendor/nova-tenancy'),
        ], 'nova-tenancy-translations');

        Nova::tools([new NovaTenancyTool]);

        $this->app->booted(fn () => $this->routes());

        Nova::serving(function (ServingNova $event): void {
            $tenant = app(CurrentTenantContext::class)->model();

            Nova::provideToScript([
                'novaTenancy' => [
                    'current' => $tenant === null ? null : TenantPayload::fromModel($tenant),
                    'apiBase' => '/'.trim((string) config('nova-tenancy.routes.api_prefix'), '/'),
                    'selectorPath' => '/'.trim((string) config('nova-tenancy.routes.uri'), '/'),
                    'searchDebounce' => (int) config('nova-tenancy.selection.search_debounce', 300),
                    'perPage' => (int) config('nova-tenancy.selection.per_page', 12),
                    'branding' => config('nova-tenancy.branding'),
                    'copy' => [
                        'pageTitle' => __('nova-tenancy::nova-tenancy.page.title'),
                        'pageKicker' => __('nova-tenancy::nova-tenancy.page.kicker'),
                        'pageDescription' => __('nova-tenancy::nova-tenancy.page.description'),
                        'currentTenant' => __('nova-tenancy::nova-tenancy.page.current'),
                        'search' => __('nova-tenancy::nova-tenancy.page.search'),
                        'searchPlaceholder' => __('nova-tenancy::nova-tenancy.page.search_placeholder'),
                        'select' => __('nova-tenancy::nova-tenancy.page.select'),
                        'selected' => __('nova-tenancy::nova-tenancy.page.selected'),
                        'emptyTitle' => __('nova-tenancy::nova-tenancy.page.empty_title'),
                        'emptyDescription' => __('nova-tenancy::nova-tenancy.page.empty_description'),
                        'error' => __('nova-tenancy::nova-tenancy.page.error'),
                        'retry' => __('nova-tenancy::nova-tenancy.page.retry'),
                        'previous' => __('nova-tenancy::nova-tenancy.page.previous'),
                        'next' => __('nova-tenancy::nova-tenancy.page.next'),
                        'selectTenant' => __('nova-tenancy::nova-tenancy.page.select_tenant'),
                    ],
                ],
            ]);
        });
    }

    protected function routes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Nova::router(['nova', 'nova.auth', Authorize::class], trim((string) config('nova-tenancy.routes.uri'), '/'))
            ->group(__DIR__.'/../routes/inertia.php');

        Route::middleware(['nova', 'nova.auth', Authorize::class])
            ->prefix(trim((string) config('nova-tenancy.routes.api_prefix'), '/'))
            ->as('nova-tenancy.api.')
            ->group(__DIR__.'/../routes/api.php');
    }
}
