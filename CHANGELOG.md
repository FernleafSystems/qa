# Changelog

All notable changes to this project will be documented in this file.

## [1.0.3] - 2026-10-29

### Fixed

- **PreCommitAction**: A tool that runs away is stopped instead of exhausting the machine
  - Each tool runs as its own process, without a shell, under the PHP that runs the hook, and is killed with any process it started above 8 GB of resident memory or after 30 minutes; the commit fails. Memory is read from the operating system, since PHP's `memory_limit` doesn't bound every allocation
  - Limits are set with the action's `max_memory_mb` and `timeout_seconds` options, or for one run with `QA_HOOK_MAX_MEMORY_MB` and `QA_HOOK_TIMEOUT_SECONDS`; `0` turns one off
  - The hook refuses to run when any package under `vendor` resolves outside the checkout (a junction or symlink to another checkout's vendor), since the tools would load that checkout's code. Rector looped, reaching 80 GB, when the two disagreed. `allow_external_tools` or `QA_HOOK_ALLOW_EXTERNAL_TOOLS=1` permits it
  - PHP-CS-Fixer runs with `--sequential`, so one process does the work, as Rector already did

### Removed

- `bin/pre-commit.php`, an unused copy of the pre-commit pipeline without these protections

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
