<?php

namespace MeghdadFadaee\NovaTenancy\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface TenantProvider
{
    public function query(Request $request): Builder;

    public function search(Request $request, Builder $query, string $search): Builder;

    public function find(Request $request, int|string $key): ?Model;
}
