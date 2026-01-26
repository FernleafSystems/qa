<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;

final class Php74 extends AbstractRuleSet {
	public function targetPhpVersion(): string {
		return '7.4';
	}

	/**
	 * @return array<class-string>
	 */
	public function rules(): array {
		// Remove PHP 8.0+ rules for PHP 7.4
		return \array_values( \array_filter(
			parent::rules(),
			static fn( string $rule ): bool => !\in_array( $rule, [
					ClassPropertyAssignToConstructorPromotionRector::class,
				], true )
		) );
	}

	/**
	 * @return array<class-string|string>
	 */
	public function skip(): array {
		return \array_merge( parent::skip(), [
			// Skip rules that produce PHP 8.0+ syntax
			ClassPropertyAssignToConstructorPromotionRector::class,
			ChangeSwitchToMatchRector::class,
		] );
	}
}
