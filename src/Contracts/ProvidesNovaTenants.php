<?php

namespace MeghdadFadaee\NovaTenancy\Contracts;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface ProvidesNovaTenants
{
    public function novaTenantQuery(Request $request): Builder;
}
