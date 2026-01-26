# Changelog

All notable changes to this project will be documented in this file.

## [1.0.1] - 2025-01-26

### Fixed

- **CI workflow**: Removed PHP 7.4 from test matrix (composer.json requires ^8.3)
- **CI workflow**: Added `qa-ci-setup` script to generate config files before running checks
- **PHPStan**: Added `composer/composer` as dev dependency to resolve `Composer\Script\Event` type errors
- **PreCommitAction**: Fixed "command line too long" error on Windows by processing staged files in batches

### Added

- `bin/qa-ci-setup`: Non-interactive config generation script for CI environments
- `composer qa:ci-setup`: Composer script alias for CI setup
- Unit tests for `PreCommitAction` batching logic

## [1.0.0] - 2025-01-25

Initial release.
