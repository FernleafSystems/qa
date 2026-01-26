<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\Rector\RuleSet;

use FernleafSystems\QA\Rector\RuleSet\Php83;
use PHPUnit\Framework\TestCase;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

final class Php83Test extends TestCase {
	private Php83 $ruleSet;

	protected function setUp(): void {
		$this->ruleSet = new Php83();
	}

	public function testTargetPhpVersion(): void {
		$this->assertSame( '8.3', $this->ruleSet->targetPhpVersion() );
	}

	public function testUsePhpSets(): void {
		$this->assertTrue( $this->ruleSet->usePhpSets() );
	}

	public function testRulesContainCommonRules(): void {
		// PHP 8.0+ specific rules (constructor promotion, match) are applied
		// via withPhpSets() in ConfigFactory, not in RuleSet::rules()
		$rules = $this->ruleSet->rules();
		$this->assertContains( AddVoidReturnTypeWhereNoReturnRector::class, $rules );
	}

	public function testSkipContainsStringClassNameRector(): void {
		$skip = $this->ruleSet->skip();
		$this->assertContains( 'Rector\Php55\Rector\String_\StringClassNameToClassConstantRector', $skip );
	}
}
