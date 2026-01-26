# Changelog

All notable changes to this project will be documented in this file.

## [1.0.2] - 2025-01-29

### Fixed

- **PreCommitAction**: Fixed false positives when files have both staged and unstaged changes
  - Capture file checksums before running formatters
  - Compare checksums instead of using git diff to detect changes
  - Only fail when formatters actually modify files
  - Improve error message to list specific modified files
  - Fixes issue where commits were blocked due to unrelated unstaged changes in files that were also being committed

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
