<?php

namespace MeghdadFadaee\NovaTenancy\Contracts;

use Closure;
use Illuminate\Database\Eloquent\Model;

interface CurrentTenantContext
{
    public function id(): int|string|null;

    /** @return (Model&NovaTenant)|null */
    public function model(): ?Model;

    public function hasTenant(): bool;

    public function requireId(): int|string;

    /** @return Model&NovaTenant */
    public function requireModel(): Model;

    /** @param Model&NovaTenant $tenant */
    public function activate(Model $tenant): void;

    public function forget(): void;

    /**
     * @template TReturn
     *
     * @param  Model&NovaTenant  $tenant
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    public function run(Model $tenant, Closure $callback): mixed;
}
