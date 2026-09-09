<?php

namespace MeghdadFadaee\NovaTenancy\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use MeghdadFadaee\NovaTenancy\Contracts\NovaTenant;

class TenantSelected
{
    use Dispatchable;

    /** @param Model&NovaTenant $tenant */
    public function __construct(
        public Authenticatable $user,
        public Model $tenant,
        public int|string|null $previousTenantId,
    ) {}
}
