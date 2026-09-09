<?php

use Illuminate\Support\Facades\Event;
use MeghdadFadaee\NovaTenancy\Events\TenantCleared;
use MeghdadFadaee\NovaTenancy\Events\TenantSelected;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\Tenant;
use MeghdadFadaee\NovaTenancy\Tests\Fixtures\User;

it('searches and paginates only the authenticated users tenants', function (): void {
    $user = User::query()->create(['name' => 'Ada']);
    $otherUser = User::query()->create(['name' => 'Grace']);
    Tenant::query()->create(['user_id' => $user->id, 'title' => 'Alpha']);
    Tenant::query()->create(['user_id' => $user->id, 'title' => 'Beta']);
    Tenant::query()->create(['user_id' => $otherUser->id, 'title' => 'Alpha Secret']);

    $this->actingAs($user)
        ->getJson('/testing/tenants?search=Alpha&perPage=1')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.title', 'Alpha');
});

it('selects an accessible tenant and returns a safe spa destination', function (): void {
    Event::fake();
    $user = User::query()->create(['name' => 'Ada']);
    $tenant = Tenant::query()->create(['user_id' => $user->id, 'title' => 'Alpha']);

    $this->actingAs($user)
        ->postJson('/testing/current', ['tenant' => $tenant->id])
        ->assertOk()
        ->assertJsonPath('current.key', $tenant->id)
        ->assertJsonPath('redirectTo', '/')
        ->assertCookie('nova_tenant', (string) $tenant->id);

    Event::assertDispatched(TenantSelected::class, fn (TenantSelected $event): bool => $event->tenant->is($tenant));
});

it('does not reveal or select another users tenant', function (): void {
    $user = User::query()->create(['name' => 'Ada']);
    $otherUser = User::query()->create(['name' => 'Grace']);
    $tenant = Tenant::query()->create(['user_id' => $otherUser->id, 'title' => 'Secret']);

    $this->actingAs($user)
        ->postJson('/testing/current', ['tenant' => (string) $tenant->id])
        ->assertNotFound()
        ->assertCookieMissing('nova_tenant');
});

it('clears the selection and dispatches an event', function (): void {
    Event::fake();
    $user = User::query()->create(['name' => 'Ada']);
    $tenant = Tenant::query()->create(['user_id' => $user->id, 'title' => 'Alpha']);

    $this->actingAs($user)
        ->withCookie('nova_tenant', (string) $tenant->id)
        ->deleteJson('/testing/current')
        ->assertOk()
        ->assertCookieExpired('nova_tenant');

    Event::assertDispatched(TenantCleared::class);
});

it('redirects safe logos without caching them', function (): void {
    $user = User::query()->create(['name' => 'Ada']);
    $tenant = Tenant::query()->create([
        'user_id' => $user->id,
        'title' => 'Alpha',
        'logo_url' => 'https://cdn.example.com/alpha.png',
    ]);

    $this->actingAs($user)
        ->withCookie('nova_tenant', (string) $tenant->id)
        ->get('/testing/current/logo')
        ->assertRedirect('https://cdn.example.com/alpha.png')
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
});

it('renders a fallback for unsafe logo schemes', function (): void {
    $user = User::query()->create(['name' => 'Ada']);
    $tenant = Tenant::query()->create([
        'user_id' => $user->id,
        'title' => 'Alpha',
        'logo_url' => 'javascript:alert(1)',
    ]);

    $this->actingAs($user)
        ->withCookie('nova_tenant', (string) $tenant->id)
        ->get('/testing/current/logo')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
});
