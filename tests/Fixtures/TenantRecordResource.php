<?php

namespace MeghdadFadaee\NovaTenancy\Tests\Fixtures;

use Laravel\Nova\Fields\FieldCollection;
use Laravel\Nova\Http\Requests\NovaRequest;
use MeghdadFadaee\NovaTenancy\Nova\TenantResource;

class TenantRecordResource extends TenantResource
{
    public static $model = TenantRecord::class;

    public function fields(NovaRequest $request): array
    {
        return [];
    }

    public function creationFields(NovaRequest $request): FieldCollection
    {
        return new FieldCollection;
    }

    public function updateFields(NovaRequest $request): FieldCollection
    {
        return new FieldCollection;
    }
}
