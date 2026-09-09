# Contributing

Thank you for considering a contribution to Nova Tenancy.

## Development setup

This package requires access to Laravel Nova. Use an existing local Nova installation or Composer authentication configured outside the repository. Never commit Nova credentials, `auth.json`, `.env` files, or vendor sources.

Install dependencies and run the verification commands:

```bash
composer install
npm install
composer test
npm test
npm run production
vendor/bin/pint --test
```

Production assets under `dist/` must be rebuilt and included when frontend source changes.

## Pull requests

- Keep changes focused and backward compatible where practical.
- Add or update tests for changed behavior.
- Update `CHANGELOG.md` under `Unreleased` for user-facing changes.
- Update the README when a public contract, configuration option, or setup step changes.
- Do not include application-specific tenant assumptions or dependencies on a full tenancy framework.

For security vulnerabilities, follow [SECURITY.md](SECURITY.md) instead of opening a public issue.
