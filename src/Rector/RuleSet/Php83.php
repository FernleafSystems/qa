<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

/**
 * PHP 8.3 ruleset.
 *
 * PHP 8.0+ features (constructor promotion, match expressions, etc.) are applied
 * automatically via withPhpSets(php83: true) in ConfigFactory.
 */
class Php83 extends AbstractRuleSet {
	public function targetPhpVersion(): string {
		return '8.3';
	}
}
