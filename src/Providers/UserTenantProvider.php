<?php

namespace MeghdadFadaee\NovaTenancy\Providers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LogicException;
use MeghdadFadaee\NovaTenancy\Contracts\ProvidesNovaTenants;

class UserTenantProvider extends EloquentTenantProvider
{
    public function find(Request $request, int|string $key): ?Model
    {
        if ($request->user() === null) {
            return null;
        }

        return parent::find($request, $key);
    }

    public function query(Request $request): Builder
    {
        $user = $request->user();

        if (! $user instanceof ProvidesNovaTenants) {
            throw new LogicException(sprintf(
                'The authenticated user must implement [%s], or configure a custom tenant provider.',
                ProvidesNovaTenants::class,
            ));
        }

        return $user->novaTenantQuery($request);
    }
}
