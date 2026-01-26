<?php declare( strict_types=1 );

use FernleafSystems\QA\PhpCsFixer\ConfigFactory;
use FernleafSystems\QA\PhpCsFixer\RuleSet\Php83;
use PhpCsFixer\Finder;

$finder = Finder::create()
	->in( __DIR__.'/src' )
	->in( __DIR__.'/tests' )
	->in( __DIR__.'/bin' )
	->exclude( 'vendor' );

return ConfigFactory::fromRuleSet( new Php83() )
	->withFinder( $finder )
	->create();
