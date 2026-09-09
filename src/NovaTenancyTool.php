<?php

namespace MeghdadFadaee\NovaTenancy;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class NovaTenancyTool extends Tool
{
    public function authorize(Request $request): bool
    {
        return parent::authorize($request) && NovaTenancy::authorizedToSee($request);
    }

    public function boot(): void
    {
        Nova::mix('nova-tenancy', __DIR__.'/../dist/mix-manifest.json');
    }

    public function menu(Request $request): ?MenuSection
    {
        if (! config('nova-tenancy.menu.sidebar', false)) {
            return null;
        }

        return MenuSection::make(NovaTenancy::menuLabel())
            ->path('/'.trim((string) config('nova-tenancy.routes.uri', 'nova-tenancy'), '/'))
            ->icon((string) config('nova-tenancy.menu.icon', 'building-office-2'));
    }
}
