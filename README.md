# FernleafSystems QA

Shared PHP code quality configuration for all FernleafSystems projects. One package gives you consistent code style, static analysis, and pre-commit hooks across all your repositories.

## Installation

```bash
composer require --dev fernleafsystems/qa
```

## Quick Start

After installing, run the setup wizard:

```bash
vendor/bin/qa-setup
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

## Running the Tools

After setup, run the tools directly via `vendor/bin/`:

```bash
# Code Style (PHP CS Fixer)
vendor/bin/php-cs-fixer fix --allow-risky=yes
vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes  # CI check

# Rector (Automated Refactoring)
vendor/bin/rector process
vendor/bin/rector process --dry-run  # CI check

# PHPStan (Static Analysis)
vendor/bin/phpstan analyse
vendor/bin/phpstan analyse --generate-baseline  # Generate baseline

# WordPress Projects Only
vendor/bin/phpcs
vendor/bin/phpcbf  # Auto-fix
```

### Optional: Add Composer Scripts

For convenience, add these to your project's `composer.json`:

```json
{
    "scripts": {
        "cs": "php-cs-fixer fix --allow-risky=yes",
        "cs-check": "php-cs-fixer fix --dry-run --diff --allow-risky=yes",
        "rector": "rector process",
        "rector-check": "rector process --dry-run",
        "phpstan": "phpstan analyse"
    }
}
```

Then run: `composer cs`, `composer rector`, etc.

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

Available rulesets: `Php83`, `Php84`, `Php85`

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

      - run: vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes

      - run: vendor/bin/rector process --dry-run

      - run: vendor/bin/phpstan analyse
```

## Supported PHP Versions

**Requires PHP 8.3+**

- PHP 8.3, 8.4
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
