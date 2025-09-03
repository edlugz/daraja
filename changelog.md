# Changelog

All notable changes to `Daraja` will be documented in this file.

## Version 0.1

### Added
- Everything

## [Unreleased]

## [1.0.0] - 2025-08-30
### Changed
- Upgraded minimum PHP requirement from **8.1** to **8.3+**.
- Updated `composer.json` constraints to support **Laravel 12**.
- Refactored internal codebase for compatibility with PHP 8.3+ features and syntax.
- Adjusted package dependencies for Laravel 12 ecosystem.

### Notes
- This release introduces **breaking changes** due to the raised PHP and Laravel version requirements.
- Ensure your environment is running PHP 8.3+ and Laravel 12 before upgrading.


## [1.1.0] - 2025-09-03
### Added
- **`daraja:install`** command to publish config/migrations and run `php artisan migrate` (`--force`, `--no-publish`, `--no-migrate` flags).
- **Auto-loaded migrations** via `loadMigrationsFrom()`, so package migrations run with `php artisan migrate` even without publishing.
- **Env-driven configuration**: `DARAJA_MODE`, `DARAJA_BASE_URL`, `DARAJA_LOG_ENABLED`, `DARAJA_LOG_LEVEL`.
- **TLS options** for tricky environments (Windows/Docker): `DARAJA_VERIFY`, `DARAJA_CA_BUNDLE`.

