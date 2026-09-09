<?php

namespace MeghdadFadaee\NovaTenancy\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\Exceptions\TenantNotSelected;
use Symfony\Component\HttpFoundation\Response;

class RequireTenant
{
    public function __construct(protected CurrentTenantContext $currentTenant) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->currentTenant->hasTenant()) {
            return (new TenantNotSelected)->toResponse($request);
        }

        return $next($request);
    }
}
