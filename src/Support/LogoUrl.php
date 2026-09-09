<?php

namespace MeghdadFadaee\NovaTenancy\Support;

final class LogoUrl
{
    public static function normalize(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            ? $url
            : null;
    }
}
