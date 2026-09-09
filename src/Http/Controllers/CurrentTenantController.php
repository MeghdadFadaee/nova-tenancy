<?php

namespace MeghdadFadaee\NovaTenancy\Http\Controllers;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\Contracts\NovaTenant;
use MeghdadFadaee\NovaTenancy\Contracts\TenantProvider;
use MeghdadFadaee\NovaTenancy\Events\TenantCleared;
use MeghdadFadaee\NovaTenancy\Events\TenantSelected;
use MeghdadFadaee\NovaTenancy\NovaTenancy;
use MeghdadFadaee\NovaTenancy\Support\TenantPayload;

class CurrentTenantController extends Controller
{
    public function show(
        Request $request,
        CurrentTenantContext $currentTenant,
        CookieJar $cookies,
    ): JsonResponse {
        $tenant = $currentTenant->model();
        $response = response()->json([
            'current' => $tenant === null ? null : TenantPayload::fromModel($tenant),
            'logoUrl' => $this->logoEndpoint(),
        ]);

        if ($tenant === null && $request->hasCookie($this->cookieName())) {
            $response->withCookie($cookies->forget(
                $this->cookieName(),
                (string) config('nova-tenancy.cookie.path', '/'),
                config('nova-tenancy.cookie.domain'),
            ));
        }

        return $response;
    }

    public function store(
        Request $request,
        TenantProvider $provider,
        CurrentTenantContext $currentTenant,
        CookieJar $cookies,
    ): JsonResponse {
        $validated = $request->validate([
            'tenant' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ((! is_string($value) && ! is_int($value)) || strlen((string) $value) > 255) {
                        $fail("The {$attribute} field must be a valid tenant key.");
                    }
                },
            ],
        ]);

        $tenant = $provider->find($request, $validated['tenant']);
        abort_unless($tenant instanceof NovaTenant, 404);

        $user = $request->user();
        abort_unless($user instanceof Authenticatable, 401);

        $previousTenantId = $currentTenant->id();
        $currentTenant->activate($tenant);

        TenantSelected::dispatch($user, $tenant, $previousTenantId);

        return response()->json([
            'current' => TenantPayload::fromModel($tenant),
            'logoUrl' => $this->logoEndpoint(),
            'redirectTo' => NovaTenancy::landingPath($request, $tenant),
        ])->withCookie($cookies->make(
            $this->cookieName(),
            (string) $tenant->novaTenantKey(),
            (int) config('nova-tenancy.cookie.minutes', 525600),
            (string) config('nova-tenancy.cookie.path', '/'),
            config('nova-tenancy.cookie.domain'),
            config('nova-tenancy.cookie.secure'),
            (bool) config('nova-tenancy.cookie.http_only', true),
            false,
            (string) config('nova-tenancy.cookie.same_site', 'lax'),
        ));
    }

    public function destroy(
        Request $request,
        CurrentTenantContext $currentTenant,
        CookieJar $cookies,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user instanceof Authenticatable, 401);

        $previousTenantId = $currentTenant->id();
        $currentTenant->forget();
        TenantCleared::dispatch($user, $previousTenantId);

        return response()->json([
            'current' => null,
            'redirectTo' => '/'.trim((string) config('nova-tenancy.routes.uri', 'nova-tenancy'), '/'),
        ])->withCookie($cookies->forget(
            $this->cookieName(),
            (string) config('nova-tenancy.cookie.path', '/'),
            config('nova-tenancy.cookie.domain'),
        ));
    }

    protected function cookieName(): string
    {
        return (string) config('nova-tenancy.cookie.name', 'nova_tenant');
    }

    protected function logoEndpoint(): string
    {
        return '/'.trim((string) config('nova-tenancy.routes.api_prefix'), '/').'/current/logo';
    }
}
