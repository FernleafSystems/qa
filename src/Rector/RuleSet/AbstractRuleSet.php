<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

abstract class AbstractRuleSet implements RuleSetInterface {
	public function usePhpSets(): bool {
		return true;
	}

	/**
	 * Common rules used across all PHP versions (from WorpDrive/Auto-AI-Translations)
	 *
	 * @return array<class-string>
	 */
	public function rules(): array {
		return [
			// Type declarations
			AddVoidReturnTypeWhereNoReturnRector::class,
			TypedPropertyFromStrictConstructorRector::class,

			// Dead code removal
			RemoveUnusedPrivateMethodRector::class,
			RemoveUnusedPrivatePropertyRector::class,
			RemoveUnusedPrivateMethodParameterRector::class,
			RemoveUnusedVariableAssignRector::class,

			// Code quality
			SimplifyUselessVariableRector::class,
			ShortenElseIfRector::class,
		];
	}

	/**
	 * Common rules to skip across all versions
	 *
	 * @return array<class-string|string>
	 */
	public function skip(): array {
		return [
			// Intentionally disabled - can cause issues with dynamic class loading
			StringClassNameToClassConstantRector::class,
		];
	}
}
