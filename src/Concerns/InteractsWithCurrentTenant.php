<?php

namespace MeghdadFadaee\NovaTenancy\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\Contracts\NovaTenant;

trait InteractsWithCurrentTenant
{
    public static function currentTenantContext(): CurrentTenantContext
    {
        return app(CurrentTenantContext::class);
    }

    public static function currentTenantId(): int|string|null
    {
        return static::currentTenantContext()->id();
    }

    /** @return (Model&NovaTenant)|null */
    public static function currentTenantModel(): ?Model
    {
        return static::currentTenantContext()->model();
    }

    public static function scopeToCurrentTenant(
        Builder $query,
        string $column = 'tenant_id',
        bool $required = true,
    ): Builder {
        $id = $required
            ? static::currentTenantContext()->requireId()
            : static::currentTenantContext()->id();

        if ($id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where($query->getModel()->qualifyColumn($column), $id);
    }
}
