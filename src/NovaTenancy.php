<?php

namespace MeghdadFadaee\NovaTenancy;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuItem;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\Contracts\NovaTenant;
use Symfony\Component\HttpFoundation\Response;

final class NovaTenancy
{
    /** @var (Closure(Request): bool)|null */
    protected static ?Closure $authorizationCallback = null;

    /** @var (Closure(Request, Model&NovaTenant): string)|null */
    protected static ?Closure $landingCallback = null;

    /** @var (Closure(Request, Model&NovaTenant): (Response|null))|null */
    protected static ?Closure $logoResponseCallback = null;

    public static function current(): CurrentTenantContext
    {
        return app(CurrentTenantContext::class);
    }

    public static function id(): int|string|null
    {
        return self::current()->id();
    }

    /** @return (Model&NovaTenant)|null */
    public static function tenant(): ?Model
    {
        return self::current()->model();
    }

    /** @param Closure(Request): bool $callback */
    public static function authorizeUsing(Closure $callback): void
    {
        self::$authorizationCallback = $callback;
    }

    public static function authorizedToSee(Request $request): bool
    {
        return self::$authorizationCallback === null
            ? $request->user() !== null
            : (bool) (self::$authorizationCallback)($request);
    }

    /** @param Closure(Request, Model&NovaTenant): string $callback */
    public static function landingUsing(Closure $callback): void
    {
        self::$landingCallback = $callback;
    }

    /** @param Model&NovaTenant $tenant */
    public static function landingPath(Request $request, Model $tenant): string
    {
        $path = self::$landingCallback === null
            ? (string) config('nova-tenancy.selection.landing_path', '/')
            : (string) (self::$landingCallback)($request, $tenant);

        if ($path === '' || str_starts_with($path, '//') || filter_var($path, FILTER_VALIDATE_URL)) {
            return '/';
        }

        return '/'.ltrim($path, '/');
    }

    /** @param Closure(Request, Model&NovaTenant): (Response|null) $callback */
    public static function logoResponseUsing(Closure $callback): void
    {
        self::$logoResponseCallback = $callback;
    }

    /** @param Model&NovaTenant $tenant */
    public static function logoResponse(Request $request, Model $tenant): ?Response
    {
        return self::$logoResponseCallback === null
            ? null
            : (self::$logoResponseCallback)($request, $tenant);
    }

    public static function userMenuItem(): MenuItem
    {
        $path = '/'.trim((string) config('nova-tenancy.routes.uri', 'nova-tenancy'), '/');

        return MenuItem::link(self::menuLabel(), $path)
            ->canSee(fn (Request $request): bool => self::authorizedToSee($request));
    }

    public static function menuLabel(): string
    {
        $label = config('nova-tenancy.menu.label');

        return is_string($label) && $label !== ''
            ? __($label)
            : __('nova-tenancy::nova-tenancy.menu.label');
    }

    public static function flushState(): void
    {
        self::$authorizationCallback = null;
        self::$landingCallback = null;
        self::$logoResponseCallback = null;
    }
}
