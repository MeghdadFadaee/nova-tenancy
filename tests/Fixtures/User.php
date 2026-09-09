<?php

namespace MeghdadFadaee\NovaTenancy\Tests\Fixtures;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use MeghdadFadaee\NovaTenancy\Contracts\ProvidesNovaTenants;

class User extends Authenticatable implements ProvidesNovaTenants
{
    protected $guarded = [];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function novaTenantQuery(Request $request): Builder
    {
        return $this->tenants()->getQuery()->orderBy('title');
    }
}
