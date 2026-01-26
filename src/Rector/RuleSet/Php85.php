<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

/**
 * PHP 8.5 ruleset.
 *
 * Extends PHP 8.4 rules. PHP 8.5-specific rules are applied via withPhpSets(php85: true) in ConfigFactory.
 */
final class Php85 extends Php84 {
	#[\Override]
	public function targetPhpVersion(): string {
		return '8.5';
	}
}
