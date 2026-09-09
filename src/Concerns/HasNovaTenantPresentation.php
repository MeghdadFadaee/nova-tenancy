<?php

namespace MeghdadFadaee\NovaTenancy\Concerns;

trait HasNovaTenantPresentation
{
    public function novaTenantKey(): int|string
    {
        return $this->getAttribute($this->novaTenantKeyColumn());
    }

    public function novaTenantTitle(): string
    {
        return (string) $this->getAttribute((string) config('nova-tenancy.model.title_attribute', 'name'));
    }

    public function novaTenantLogoUrl(): ?string
    {
        $value = $this->getAttribute((string) config('nova-tenancy.model.logo_attribute', 'logo_url'));

        return filled($value) ? (string) $value : null;
    }

    public function novaTenantMeta(): array
    {
        return array_filter([
            'description' => $this->novaTenantMetaAttribute('description_attribute'),
            'badge' => $this->novaTenantMetaAttribute('badge_attribute'),
            'accent' => $this->novaTenantMetaAttribute('accent_attribute'),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function novaTenantKeyColumn(): string
    {
        return (string) (config('nova-tenancy.model.key_column') ?: $this->getRouteKeyName());
    }

    protected function novaTenantMetaAttribute(string $configurationKey): ?string
    {
        $attribute = config("nova-tenancy.model.{$configurationKey}");

        if (! is_string($attribute) || $attribute === '') {
            return null;
        }

        $value = $this->getAttribute($attribute);

        return filled($value) ? (string) $value : null;
    }
}
