<?php

namespace MeghdadFadaee\NovaTenancy\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;

class TenantCleared
{
    use Dispatchable;

    public function __construct(
        public Authenticatable $user,
        public int|string|null $previousTenantId,
    ) {}
}
