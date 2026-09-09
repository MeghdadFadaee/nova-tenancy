<?php

use MeghdadFadaee\NovaTenancy\Support\TenantPayload;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\Tenant;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\User;

it('normalizes model presentation and rejects unsafe accent values', function (): void {
    $user = User::query()->create(['name' => 'Ada']);
    $tenant = Tenant::query()->create([
        'user_id' => $user->id,
        'title' => 'Alpha',
        'description' => 'Primary workspace',
        'logo_url' => 'javascript:alert(1)',
        'accent' => 'red; background: url(evil)',
    ]);

    expect(TenantPayload::fromModel($tenant))->toMatchArray([
        'key' => $tenant->id,
        'title' => 'Alpha',
        'description' => 'Primary workspace',
        'logoUrl' => null,
        'accent' => null,
    ]);

    $tenant->accent = '#7c3aed';
    expect(TenantPayload::fromModel($tenant)['accent'])->toBe('#7c3aed');
});
