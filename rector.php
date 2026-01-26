<?php declare( strict_types=1 );

/**
 * Rector configuration.
 * Rules are loaded from this package's own rulesets.
 */

use FernleafSystems\QA\Rector\ConfigFactory;

return ConfigFactory::forPhpVersion( '8.3' )
	->withPaths( [
		__DIR__.'/src',
		__DIR__.'/tests',
		__DIR__.'/bin',
	] )
	->withSkip( [
		__DIR__.'/vendor',
	] )
	->create();
