<?php

namespace MeghdadFadaee\NovaTenancy\Providers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MeghdadFadaee\NovaTenancy\Contracts\TenantProvider;

abstract class EloquentTenantProvider implements TenantProvider
{
    public function search(Request $request, Builder $query, string $search): Builder
    {
        $columns = $this->searchColumns($request);

        if ($search === '' || $columns === []) {
            return $query;
        }

        return $query->where(function ($query) use ($columns, $search): void {
            foreach ($columns as $column) {
                $query->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    public function find(Request $request, int|string $key): ?Model
    {
        $query = $this->query($request);
        $model = $query->getModel();

        return $query->where(
            $model->qualifyColumn($this->keyColumn($request, $model)),
            $key,
        )->first();
    }

    /** @return array<int, string> */
    protected function searchColumns(Request $request): array
    {
        return array_values(array_filter(
            (array) config('nova-tenancy.model.search_columns', ['name']),
            static fn (mixed $column): bool => is_string($column) && $column !== '',
        ));
    }

    protected function keyColumn(Request $request, Model $model): string
    {
        return (string) (config('nova-tenancy.model.key_column') ?: $model->getRouteKeyName());
    }
}
