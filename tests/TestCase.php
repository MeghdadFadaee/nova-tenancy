<?php

namespace MeghdadFadaee\NovaTenancy\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Nova\NovaCoreServiceProvider;
use MeghdadFadaee\NovaTenancy\Http\Controllers\CurrentTenantController;
use MeghdadFadaee\NovaTenancy\Http\Controllers\CurrentTenantLogoController;
use MeghdadFadaee\NovaTenancy\Http\Controllers\TenantIndexController;
use MeghdadFadaee\NovaTenancy\NovaTenancy;
use MeghdadFadaee\NovaTenancy\NovaTenancyServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            NovaCoreServiceProvider::class,
            NovaTenancyServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('n', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', Fixtures\User::class);
        $app['config']->set('nova-tenancy.model.title_attribute', 'title');
        $app['config']->set('nova-tenancy.model.logo_attribute', 'logo_url');
        $app['config']->set('nova-tenancy.model.description_attribute', 'description');
        $app['config']->set('nova-tenancy.model.accent_attribute', 'accent');
        $app['config']->set('nova-tenancy.model.search_columns', ['title']);
        $app['config']->set('nova-tenancy.resources.tenant_column', 'tenant_id');
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->group(function (): void {
            Route::get('/testing/tenants', TenantIndexController::class);
            Route::get('/testing/current', [CurrentTenantController::class, 'show']);
            Route::post('/testing/current', [CurrentTenantController::class, 'store']);
            Route::delete('/testing/current', [CurrentTenantController::class, 'destroy']);
            Route::get('/testing/current/logo', CurrentTenantLogoController::class);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        NovaTenancy::flushState();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('title');
            $table->string('logo_url')->nullable();
            $table->string('description')->nullable();
            $table->string('accent')->nullable();
            $table->timestamps();
        });
        Schema::create('tenant_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id');
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        NovaTenancy::flushState();
        parent::tearDown();
    }
}
