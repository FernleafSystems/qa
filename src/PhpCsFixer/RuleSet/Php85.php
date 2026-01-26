<?php declare( strict_types=1 );

namespace FernleafSystems\QA\PhpCsFixer\RuleSet;

/**
 * PHP 8.5 ruleset - extends PHP 8.4 rules.
 *
 * Add PHP 8.5-specific rules as they become available in PHP CS Fixer.
 */
final class Php85 extends Php84 {
	public function targetPhpVersion(): string {
		return '8.5';
	}
}
