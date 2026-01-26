<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

/**
 * PHP 8.5 ruleset.
 *
 * Currently extends PHP 8.4 as PHP 8.5 is still in development
 * and no PHP 8.5-specific Rector rules exist yet.
 * This class will be updated when PHP 8.5-specific rules become available.
 */
final class Php85 extends Php84 {
	public function targetPhpVersion(): string {
		return '8.5';
	}
}
