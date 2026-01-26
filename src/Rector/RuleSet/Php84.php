<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

final class Php84 extends Php83 {
	public function targetPhpVersion(): string {
		return '8.4';
	}
}
