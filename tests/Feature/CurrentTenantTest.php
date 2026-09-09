<?php

use Illuminate\Http\Request;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\CurrentTenant;
use MeghdadFadaee\NovaTenancy\Exceptions\TenantNotSelected;
use MeghdadFadaee\NovaTenancy\NovaTenancy;
use MeghdadFadaee\NovaTenancy\Providers\UserTenantProvider;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\Tenant;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\User;

it('resolves only an accessible tenant from the cookie', function (): void {
    $user = User::query()->create(['name' => 'Ada']);
    $otherUser = User::query()->create(['name' => 'Grace']);
    $tenant = Tenant::query()->create(['user_id' => $user->id, 'title' => 'North']);
    $inaccessible = Tenant::query()->create(['user_id' => $otherUser->id, 'title' => 'South']);

    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);
    $request->cookies->set('nova_tenant', (string) $tenant->id);
    $context = new CurrentTenant(new UserTenantProvider, $request);

    expect($context->id())->toBe($tenant->id)
        ->and($context->model()?->is($tenant))->toBeTrue();

    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);
    $request->cookies->set('nova_tenant', (string) $inaccessible->id);

    expect((new CurrentTenant(new UserTenantProvider, $request))->model())->toBeNull();
});

it('ignores a persisted tenant cookie for unauthenticated requests', function (): void {
    $request = Request::create('/nova/login');
    $request->cookies->set('nova_tenant', '1');

    $context = new CurrentTenant(new UserTenantProvider, $request);

    expect($context->model())->toBeNull();
});

it('provides scoped string bindings and strict accessors', function (): void {
    expect(app('CurrentTenantId'))->toBeNull()
        ->and(app('CurrentTenantModel'))->toBeNull()
        ->and(fn () => app(CurrentTenantContext::class)->requireModel())
        ->toThrow(TenantNotSelected::class);
});

it('redirects an inertia page request to tenant selection', function (): void {
    $request = Request::create('/nova/dashboards/home', server: [
        'HTTP_ACCEPT' => 'text/html, application/xhtml+xml',
        'HTTP_X_INERTIA' => 'true',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
    ]);

    $response = (new TenantNotSelected)->toResponse($request);

    expect($response->getStatusCode())->toBe(302)
        ->and($response->headers->get('Location'))->toEndWith('/nova/nova-tenancy');
});

it('returns a conflict response for a json api request', function (): void {
    $request = Request::create('/nova-api/resources', server: [
        'HTTP_ACCEPT' => 'application/json',
    ]);

    $response = (new TenantNotSelected)->toResponse($request);

    expect($response->getStatusCode())->toBe(409)
        ->and($response->getContent())->toContain('tenant_selection_required');
});

it('temporarily activates a tenant and restores the previous context', function (): void {
    $user = User::query()->create(['name' => 'Ada']);
    $first = Tenant::query()->create(['user_id' => $user->id, 'title' => 'First']);
    $second = Tenant::query()->create(['user_id' => $user->id, 'title' => 'Second']);
    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);
    $context = new CurrentTenant(new UserTenantProvider, $request);
    $context->activate($first);

    $inside = $context->run($second, fn () => $context->id());

    expect($inside)->toBe($second->id)
        ->and($context->id())->toBe($first->id);
});

it('builds an official nova user menu item without replacing the menu callback', function (): void {
    $item = NovaTenancy::userMenuItem();

    expect($item->path)->toBe('/nova-tenancy')
        ->and((string) $item->name)->toBe('Select tenant');
});
