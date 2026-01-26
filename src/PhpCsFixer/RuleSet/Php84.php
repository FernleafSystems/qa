<?php declare( strict_types=1 );

namespace FernleafSystems\QA\PhpCsFixer\RuleSet;

/**
 * PHP 8.4 ruleset - extends PHP 8.3 rules.
 *
 * Add PHP 8.4-specific rules as they become available in PHP CS Fixer.
 */
class Php84 extends Php83 {
	public function targetPhpVersion(): string {
		return '8.4';
	}
}
