<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\Rector\RuleSet;

use FernleafSystems\QA\Rector\RuleSet\Php83;
use PHPUnit\Framework\TestCase;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

final class Php83Test extends TestCase {
	/**
	 * @var Php83
	 */
	private $ruleSet;

	protected function setUp(): void {
		$this->ruleSet = new Php83();
	}

	public function testTargetPhpVersion(): void {
		$this->assertSame( '8.3', $this->ruleSet->targetPhpVersion() );
	}

	public function testUsePhpSets(): void {
		$this->assertTrue( $this->ruleSet->usePhpSets() );
	}

	public function testRulesContainPhp8Features(): void {
		$rules = $this->ruleSet->rules();
		$this->assertContains( ClassPropertyAssignToConstructorPromotionRector::class, $rules );
		$this->assertContains( ChangeSwitchToMatchRector::class, $rules );
	}

	public function testRulesContainCommonRules(): void {
		$rules = $this->ruleSet->rules();
		$this->assertContains( AddVoidReturnTypeWhereNoReturnRector::class, $rules );
	}

	public function testSkipContainsStringClassNameRector(): void {
		$skip = $this->ruleSet->skip();
		$this->assertContains( 'Rector\Php55\Rector\String_\StringClassNameToClassConstantRector', $skip );
	}
}
