<?php declare( strict_types=1 );

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

$config = RectorConfig::configure()
	->withPaths( [
		__DIR__.'/src',
		__DIR__.'/tests',
	] )
	->withSkip( [
		__DIR__.'/vendor',
		StringClassNameToClassConstantRector::class,
	] )
	->withSets( [
		LevelSetList::UP_TO_PHP_74,
	] )
	->withRules( [
		ShortenElseIfRector::class,
		AddVoidReturnTypeWhereNoReturnRector::class,
		TypedPropertyFromStrictConstructorRector::class,
		RemoveUnusedPrivateMethodRector::class,
		RemoveUnusedPrivatePropertyRector::class,
		RemoveUnusedPrivateMethodParameterRector::class,
		RemoveUnusedVariableAssignRector::class,
		SimplifyUselessVariableRector::class,
	] )
	->withImportNames(
		importShortClasses: false,
		removeUnusedImports: true
	);

if ( (bool) getenv( 'RECTOR_DISABLE_PARALLEL' ) ) {
	return $config->withoutParallel();
}

return $config->withParallel( 360, 4 );
