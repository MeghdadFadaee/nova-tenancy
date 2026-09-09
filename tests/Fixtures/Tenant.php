<?php

namespace MeghdadFadaee\NovaTenancy\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MeghdadFadaee\NovaTenancy\Concerns\HasNovaTenantPresentation;
use MeghdadFadaee\NovaTenancy\Contracts\NovaTenant;

class Tenant extends Model implements NovaTenant
{
    use HasNovaTenantPresentation;

    protected $guarded = [];
}
