<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\PhpCsFixer;

use FernleafSystems\QA\PhpCsFixer\ConfigFactory;
use FernleafSystems\QA\PhpCsFixer\RuleSet\Php83;
use PhpCsFixer\Config;
use PHPUnit\Framework\TestCase;

final class ConfigFactoryTest extends TestCase {
	public function testFromRuleSetReturnsFactory(): void {
		$factory = ConfigFactory::fromRuleSet( new Php83() );
		$this->assertInstanceOf( ConfigFactory::class, $factory );
	}

	public function testCreateReturnsConfig(): void {
		$config = ConfigFactory::fromRuleSet( new Php83() )
			->withPaths( [__DIR__] )
			->create();

		$this->assertInstanceOf( Config::class, $config );
	}

	public function testConfigUsesTabsForIndentation(): void {
		$config = ConfigFactory::fromRuleSet( new Php83() )
			->withPaths( [__DIR__] )
			->create();

		$this->assertSame( "\t", $config->getIndent() );
	}

	public function testConfigUsesUnixLineEndings(): void {
		$config = ConfigFactory::fromRuleSet( new Php83() )
			->withPaths( [__DIR__] )
			->create();

		$this->assertSame( "\n", $config->getLineEnding() );
	}

	public function testConfigAllowsRiskyRules(): void {
		$config = ConfigFactory::fromRuleSet( new Php83() )
			->withPaths( [__DIR__] )
			->create();

		$this->assertTrue( $config->getRiskyAllowed() );
	}

	public function testWithPathsIsImmutable(): void {
		$factory1 = ConfigFactory::fromRuleSet( new Php83() );
		$factory2 = $factory1->withPaths( [__DIR__] );

		$this->assertNotSame( $factory1, $factory2 );
	}

	public function testWithCustomRulesMerges(): void {
		$config = ConfigFactory::fromRuleSet( new Php83() )
			->withPaths( [__DIR__] )
			->withCustomRules( ['single_quote' => false] )
			->create();

		$rules = $config->getRules();
		$this->assertFalse( $rules['single_quote'] );
	}
}
