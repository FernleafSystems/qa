<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;

final class Php83 extends AbstractRuleSet {
	public function targetPhpVersion(): string {
		return '8.3';
	}

	/**
	 * @return array<class-string>
	 */
	public function rules(): array {
		return \array_merge( parent::rules(), [
			// PHP 8.0+ features
			ClassPropertyAssignToConstructorPromotionRector::class,
			ChangeSwitchToMatchRector::class,
		] );
	}
}
