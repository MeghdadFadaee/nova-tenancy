<?php

namespace MeghdadFadaee\NovaTenancy\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use MeghdadFadaee\NovaTenancy\Contracts\NovaTenant;

final class TenantPayload
{
    /**
     * @param  Model&NovaTenant  $tenant
     * @return array{key: int|string, title: string, logoUrl: string|null, description: string|null, badge: string|null, accent: string|null}
     */
    public static function fromModel(Model $tenant): array
    {
        if (! $tenant instanceof NovaTenant) {
            throw new InvalidArgumentException(sprintf(
                'Tenant model [%s] must implement [%s].',
                $tenant::class,
                NovaTenant::class,
            ));
        }

        $meta = $tenant->novaTenantMeta();

        return [
            'key' => $tenant->novaTenantKey(),
            'title' => $tenant->novaTenantTitle(),
            'logoUrl' => LogoUrl::normalize($tenant->novaTenantLogoUrl()),
            'description' => self::nullableString($meta['description'] ?? null),
            'badge' => self::nullableString($meta['badge'] ?? null),
            'accent' => self::validAccent($meta['accent'] ?? null),
        ];
    }

    protected static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    protected static function validAccent(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        return preg_match('/^#[0-9a-f]{6}$/i', $value) === 1 ? $value : null;
    }
}
