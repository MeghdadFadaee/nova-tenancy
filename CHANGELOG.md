# Changelog

All notable changes to this project will be documented in this file.

## [1.0.1] - 2026-09-09

### Changed

- Make the selector palette, overview, surfaces, typography, focus states, and default tenant styling inherit Nova's configured brand and neutral colors.

### Fixed

- Ignore a persisted tenant cookie while Nova is serving an unauthenticated page, such as the login screen.
- Keep controls readable when a tenant uses a light accent color such as white.

## [1.0.0] - 2026-09-09

### Added

- Native Nova tenant selector with server-side search and pagination.
- Request-scoped current tenant context and container aliases.
- Cookie-backed SPA tenant switching and dynamic Nova branding.
- Tenant presentation, provider, user access, and context contracts.
- Optional tenant-aware Nova resource base and reusable context helpers.
- English and Persian translations.

[1.0.1]: https://github.com/MeghdadFadaee/nova-tenancy/releases/tag/v1.0.1
[1.0.0]: https://github.com/MeghdadFadaee/nova-tenancy/releases/tag/v1.0.0
