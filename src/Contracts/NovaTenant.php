<?php

namespace MeghdadFadaee\NovaTenancy\Contracts;

interface NovaTenant
{
    public function novaTenantKey(): int|string;

    public function novaTenantTitle(): string;

    public function novaTenantLogoUrl(): ?string;

    /**
     * @return array{description?: string|null, badge?: string|null, accent?: string|null}
     */
    public function novaTenantMeta(): array;
}
