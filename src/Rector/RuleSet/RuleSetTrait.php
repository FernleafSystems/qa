<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

trait RuleSetTrait {
	/**
	 * Apply FernleafSystems standard rules for PHP 8.0+
	 */
	public static function applyPhp8Rules( RectorConfig $config ): void {
		$config->rules( [
			ClassPropertyAssignToConstructorPromotionRector::class,
			ChangeSwitchToMatchRector::class,
			AddVoidReturnTypeWhereNoReturnRector::class,
			TypedPropertyFromStrictConstructorRector::class,
			RemoveUnusedPrivateMethodRector::class,
			RemoveUnusedPrivatePropertyRector::class,
			RemoveUnusedPrivateMethodParameterRector::class,
			RemoveUnusedVariableAssignRector::class,
			SimplifyUselessVariableRector::class,
			ShortenElseIfRector::class,
		] );

		$config->skip( [
			StringClassNameToClassConstantRector::class,
		] );

		$config->importNames();
		$config->importShortClasses( false );
		$config->removeUnusedImports();
	}

	/**
	 * Apply FernleafSystems standard rules for PHP 7.4
	 */
	public static function applyPhp74Rules( RectorConfig $config ): void {
		$config->rules( [
			AddVoidReturnTypeWhereNoReturnRector::class,
			TypedPropertyFromStrictConstructorRector::class,
			RemoveUnusedPrivateMethodRector::class,
			RemoveUnusedPrivatePropertyRector::class,
			RemoveUnusedPrivateMethodParameterRector::class,
			RemoveUnusedVariableAssignRector::class,
			SimplifyUselessVariableRector::class,
			ShortenElseIfRector::class,
		] );

		$config->skip( [
			StringClassNameToClassConstantRector::class,
			ClassPropertyAssignToConstructorPromotionRector::class,
			ChangeSwitchToMatchRector::class,
		] );

		$config->importNames();
		$config->importShortClasses( false );
		$config->removeUnusedImports();
	}
}
