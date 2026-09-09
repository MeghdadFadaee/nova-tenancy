<?php

namespace MeghdadFadaee\NovaTenancy;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use MeghdadFadaee\NovaTenancy\Contracts\CurrentTenantContext;
use MeghdadFadaee\NovaTenancy\Contracts\NovaTenant;
use MeghdadFadaee\NovaTenancy\Contracts\TenantProvider;
use MeghdadFadaee\NovaTenancy\Exceptions\TenantNotSelected;

class CurrentTenant implements CurrentTenantContext
{
    protected bool $resolved = false;

    /** @var (Model&NovaTenant)|null */
    protected ?Model $tenant = null;

    public function __construct(
        protected TenantProvider $provider,
        protected Request $request,
    ) {}

    public function id(): int|string|null
    {
        return $this->model()?->novaTenantKey();
    }

    public function model(): ?Model
    {
        if ($this->resolved) {
            return $this->tenant;
        }

        $this->resolved = true;
        $key = $this->request->cookie((string) config('nova-tenancy.cookie.name', 'nova_tenant'));

        if ($key === null || $key === '') {
            return null;
        }

        $tenant = $this->provider->find($this->request, $key);

        if ($tenant === null) {
            return null;
        }

        $this->assertTenant($tenant);

        return $this->tenant = $tenant;
    }

    public function hasTenant(): bool
    {
        return $this->model() !== null;
    }

    public function requireId(): int|string
    {
        return $this->requireModel()->novaTenantKey();
    }

    public function requireModel(): Model
    {
        return $this->model() ?? throw new TenantNotSelected;
    }

    public function activate(Model $tenant): void
    {
        $this->assertTenant($tenant);
        $this->tenant = $tenant;
        $this->resolved = true;
    }

    public function forget(): void
    {
        $this->tenant = null;
        $this->resolved = true;
    }

    public function run(Model $tenant, Closure $callback): mixed
    {
        $previousTenant = $this->tenant;
        $wasResolved = $this->resolved;

        try {
            $this->activate($tenant);

            return $callback();
        } finally {
            $this->tenant = $previousTenant;
            $this->resolved = $wasResolved;
        }
    }

    protected function assertTenant(Model $tenant): void
    {
        if (! $tenant instanceof NovaTenant) {
            throw new InvalidArgumentException(sprintf(
                'Tenant model [%s] must implement [%s].',
                $tenant::class,
                NovaTenant::class,
            ));
        }
    }
}
