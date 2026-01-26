# FernleafSystems QA

Shared PHP code quality configuration for all FernleafSystems projects. One package gives you consistent code style, static analysis, and pre-commit hooks across all your repositories.

## Installation

```bash
composer require --dev fernleafsystems/qa
```

## Quick Start

**Step 1:** Run the setup wizard to generate configuration files:

```bash
vendor/bin/qa-setup
```

**Step 2:** Install the git hooks:

```bash
composer install
```

Both steps are required. The wizard generates config files including `captainhook.json`. The `composer install` reads that file and installs the actual git hooks.

The wizard will ask you:
- PHP version (detected from your `composer.json`)
- Source directory (defaults to `src`, `lib`, `app` if found)
- Whether to include your `tests` directory
- PHPStan strictness level (3 = conservative, 6 = strict)

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

**Important:** Git hooks require TWO steps to install:

1. Run `vendor/bin/qa-setup` - this generates `captainhook.json`
2. Run `composer install` - this triggers CaptainHook to install the git hooks

Without both steps, hooks will NOT be installed.

Once installed, on every commit:

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

### Git hooks not running

You must complete BOTH steps:

1. `vendor/bin/qa-setup` - generates `captainhook.json`
2. `composer install` - installs the git hooks

Check that `captainhook.json` exists in your project root. If not, run `vendor/bin/qa-setup`.

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
