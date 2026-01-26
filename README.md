# FernleafSystems QA

Shared PHP code quality configuration for all FernleafSystems projects. One package gives you consistent code style, static analysis, and pre-commit hooks across all your repositories.

## Installation

```bash
composer require --dev fernleafsystems/qa
```

## Quick Start

After installing, run the setup wizard:

```bash
composer qa:setup
```

The wizard will:
1. Detect your PHP version from `composer.json`
2. Ask for your source directory (defaults to `src`, `lib`, `app` if found)
3. Ask if you want to include your `tests` directory
4. Ask for PHPStan strictness level (3 = conservative, 6 = strict)
5. Generate all configuration files

## What Gets Generated

| File | Purpose |
|------|---------|
| `.php-cs-fixer.php` | Code style rules (non-WordPress projects) |
| `.phpcs.xml.dist` | WordPress Coding Standards (WordPress projects) |
| `rector.php` | Automated refactoring rules |
| `phpstan.neon` | Static analysis configuration |
| `captainhook.json` | Pre-commit hook configuration |
| `.gitattributes` | Line endings, diff settings, export-ignore rules |

## Available Commands

After setup, these composer scripts are available:

```bash
# Code Style (PHP CS Fixer)
composer cs              # Fix code style issues
composer cs-check        # Check without fixing (CI)

# Rector (Automated Refactoring)
composer rector          # Apply refactoring
composer rector-check    # Check without applying (CI)

# PHPStan (Static Analysis)
composer phpstan         # Run analysis
composer phpstan-baseline # Generate baseline for existing issues

# WordPress Projects Only
composer phpcs           # Run PHP CodeSniffer
composer phpcbf          # Auto-fix CodeSniffer issues

# Tests
composer test            # Run PHPUnit
```

## Pre-Commit Hooks

The package installs git hooks automatically via CaptainHook. On every commit:

1. Rector runs on staged PHP files (with parallel mode disabled)
2. PHP CS Fixer runs on staged PHP files
3. If files were modified, the commit is blocked with a message to re-stage

This ensures all committed code meets quality standards.

## Manual Usage (Without Setup Wizard)

If you prefer to create configs manually, here's a minimal `.php-cs-fixer.php`:

```php
<?php declare( strict_types=1 );

use FernleafSystems\QA\PhpCsFixer\ConfigFactory;
use FernleafSystems\QA\PhpCsFixer\RuleSet\Php83;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in( __DIR__.'/src' )
    ->exclude( 'vendor' );

return ConfigFactory::fromRuleSet( new Php83() )
    ->withFinder( $finder )
    ->create();
```

Available rulesets: `Php74`, `Php83`, `Php84`, `Php85`

## Customizing Rules

### Adding Custom CS Fixer Rules

```php
return ConfigFactory::fromRuleSet( new Php83() )
    ->withFinder( $finder )
    ->withCustomRules( [
        'some_rule' => true,
        'another_rule' => ['option' => 'value'],
    ] )
    ->create();
```

### Excluding Paths

Edit the generated config files directly. For example, in `rector.php`:

```php
->withSkip( [
    __DIR__.'/vendor',
    __DIR__.'/legacy',
    __DIR__.'/generated',
] )
```

### Adjusting PHPStan Level

Edit `phpstan.neon`:

```neon
parameters:
    level: 6  # Change from 3 to 6 for stricter analysis
```

## CI Integration

Example GitHub Actions workflow:

```yaml
name: Code Quality

on: [push, pull_request]

jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - run: composer install --no-progress

      - run: composer cs-check

      - run: composer rector-check

      - run: composer phpstan
```

## Supported PHP Versions

- PHP 7.4 (generates configs without named arguments)
- PHP 8.0, 8.1, 8.2, 8.3, 8.4
- PHP 8.5 (forward-compatible, uses 8.4 rules until 8.5-specific rules exist)

## Coding Standards

This package enforces FernleafSystems coding standards:

- Tabs for indentation
- No spaces in string concatenation: `'foo'.$bar`
- Spaces inside parentheses: `function( $param )`
- Opening braces on same line
- PSR-12 as base with customizations
- Strict types declaration on same line as opening tag

## Troubleshooting

### Pre-commit hook blocks my commit

The hook detected that Rector or CS Fixer modified your files. Run:

```bash
git add -u
git commit
```

### Tools not found after install

Run `composer install` again to trigger the CaptainHook installer.

### Skip pre-commit hooks (emergency only)

```bash
git commit --no-verify
```
