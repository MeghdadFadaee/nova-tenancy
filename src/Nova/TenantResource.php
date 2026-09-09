<?php

namespace MeghdadFadaee\NovaTenancy\Nova;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Resource;
use Laravel\Scout\Builder as ScoutBuilder;
use MeghdadFadaee\NovaTenancy\Concerns\InteractsWithCurrentTenant;

abstract class TenantResource extends Resource
{
    use InteractsWithCurrentTenant;

    final public static function indexQuery(NovaRequest $request, Builder $query): Builder
    {
        return static::afterTenantScope($request, static::tenantScope(parent::indexQuery($request, $query)), 'index');
    }

    final public static function detailQuery(NovaRequest $request, Builder $query): Builder
    {
        return static::afterTenantScope($request, static::tenantScope(parent::detailQuery($request, $query)), 'detail');
    }

    final public static function editQuery(NovaRequest $request, Builder $query): Builder
    {
        return static::afterTenantScope($request, static::tenantScope(parent::editQuery($request, $query)), 'edit');
    }

    final public static function replicateQuery(NovaRequest $request, Builder $query): Builder
    {
        return static::afterTenantScope($request, static::tenantScope(parent::replicateQuery($request, $query)), 'replicate');
    }

    final public static function relatableQuery(NovaRequest $request, Builder $query): Builder
    {
        return static::afterTenantScope($request, static::tenantScope(parent::relatableQuery($request, $query)), 'relatable');
    }

    final public static function scoutQuery(NovaRequest $request, ScoutBuilder $query): ScoutBuilder
    {
        return static::afterTenantScoutScope(
            $request,
            parent::scoutQuery($request, $query)->where(static::tenantColumn(), static::currentTenantContext()->requireId()),
        );
    }

    final public static function fill(NovaRequest $request, $model): array
    {
        [$model, $callbacks] = parent::fill($request, $model);
        $model->setAttribute(static::tenantColumn(), static::currentTenantContext()->requireId());

        return static::afterTenantFill($request, $model, $callbacks, false);
    }

    final public static function fillForUpdate(NovaRequest $request, $model): array
    {
        [$model, $callbacks] = parent::fillForUpdate($request, $model);
        $model->setAttribute(static::tenantColumn(), static::currentTenantContext()->requireId());

        return static::afterTenantFill($request, $model, $callbacks, true);
    }

    protected static function tenantColumn(): string
    {
        return (string) config('nova-tenancy.resources.tenant_column', 'tenant_id');
    }

    protected static function tenantScope(Builder $query): Builder
    {
        return static::scopeToCurrentTenant($query, static::tenantColumn());
    }

    protected static function afterTenantScope(
        NovaRequest $request,
        Builder $query,
        string $operation,
    ): Builder {
        return $query;
    }

    protected static function afterTenantScoutScope(NovaRequest $request, ScoutBuilder $query): ScoutBuilder
    {
        return $query;
    }

    /**
     * @param  array<int, callable>  $callbacks
     * @return array{Model, array<int, callable>}
     */
    protected static function afterTenantFill(
        NovaRequest $request,
        $model,
        array $callbacks,
        bool $updating,
    ): array {
        return [$model, $callbacks];
    }
}
