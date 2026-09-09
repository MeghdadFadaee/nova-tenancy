<?php

namespace MeghdadFadaee\NovaTenancy\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\NovaTenancy;
use MeghdadFadaee\NovaTenancy\Support\LogoUrl;
use Symfony\Component\HttpFoundation\Response;

class CurrentTenantLogoController extends Controller
{
    public function __invoke(Request $request, CurrentTenantContext $currentTenant): Response
    {
        $tenant = $currentTenant->model();

        if ($tenant !== null && ($response = NovaTenancy::logoResponse($request, $tenant)) !== null) {
            return $this->withoutCaching($response);
        }

        $logoUrl = $tenant?->novaTenantLogoUrl() ?: config('nova-tenancy.branding.fallback_logo');

        if (($logoUrl = LogoUrl::normalize($logoUrl)) !== null) {
            $response = str_starts_with($logoUrl, '/')
                ? redirect()->to($logoUrl)
                : redirect()->away($logoUrl);

            return $this->withoutCaching($response);
        }

        return $this->withoutCaching(response(
            $this->fallbackSvg($tenant?->novaTenantTitle()),
            200,
            ['Content-Type' => 'image/svg+xml; charset=UTF-8'],
        ));
    }

    protected function fallbackSvg(?string $title): string
    {
        $initial = Str::upper(Str::substr(trim((string) $title), 0, 1));
        $initial = htmlspecialchars($initial !== '' ? $initial : 'T', ENT_QUOTES | ENT_XML1, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96" role="img">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#7c3aed"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs>
  <rect width="96" height="96" rx="24" fill="url(#g)"/>
  <text x="48" y="61" text-anchor="middle" font-family="ui-sans-serif,system-ui,sans-serif" font-size="42" font-weight="700" fill="white">{$initial}</text>
</svg>
SVG;
    }

    protected function withoutCaching(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
