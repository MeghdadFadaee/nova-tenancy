<?php

use Laravel\Nova\Http\Requests\NovaRequest;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\Exceptions\TenantNotSelected;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\Tenant;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\TenantRecord;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\TenantRecordResource;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\User;

it('scopes all eloquent resource query entry points', function (): void {
    $user = User::query()->create(['name' => 'Ada']);
    $first = Tenant::query()->create(['user_id' => $user->id, 'title' => 'First']);
    $second = Tenant::query()->create(['user_id' => $user->id, 'title' => 'Second']);
    $visible = TenantRecord::query()->create(['tenant_id' => $first->id, 'name' => 'Visible']);
    TenantRecord::query()->create(['tenant_id' => $second->id, 'name' => 'Hidden']);
    app(CurrentTenantContext::class)->activate($first);
    $request = NovaRequest::create('/nova-api/tenant-records');

    foreach (['indexQuery', 'detailQuery', 'editQuery', 'replicateQuery', 'relatableQuery'] as $method) {
        $ids = TenantRecordResource::{$method}($request, TenantRecord::query())->pluck('id')->all();
        expect($ids)->toBe([$visible->id]);
    }
});

it('stamps the selected tenant when creating and updating', function (): void {
    $user = User::query()->create(['name' => 'Ada']);
    $first = Tenant::query()->create(['user_id' => $user->id, 'title' => 'First']);
    $second = Tenant::query()->create(['user_id' => $user->id, 'title' => 'Second']);
    app(CurrentTenantContext::class)->activate($first);
    $request = NovaRequest::create('/nova-api/tenant-records', 'POST');

    [$newModel] = TenantRecordResource::fill($request, new TenantRecord(['tenant_id' => $second->id]));
    expect($newModel->tenant_id)->toBe($first->id);

    $existing = new TenantRecord(['tenant_id' => $second->id]);
    [$updatedModel] = TenantRecordResource::fillForUpdate($request, $existing);
    expect($updatedModel->tenant_id)->toBe($first->id);
});

it('rejects tenant aware resource queries without a selection', function (): void {
    $request = NovaRequest::create('/nova-api/tenant-records');

    expect(fn () => TenantRecordResource::indexQuery($request, TenantRecord::query()))
        ->toThrow(TenantNotSelected::class);
});
