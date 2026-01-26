<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

class Php84 extends Php83 {
	#[\Override]
	public function targetPhpVersion(): string {
		return '8.4';
	}
}
