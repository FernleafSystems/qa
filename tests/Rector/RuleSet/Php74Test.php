<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\Rector\RuleSet;

use FernleafSystems\QA\Rector\RuleSet\Php74;
use PHPUnit\Framework\TestCase;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

final class Php74Test extends TestCase {
	/**
	 * @var Php74
	 */
	private $ruleSet;

	protected function setUp(): void {
		$this->ruleSet = new Php74();
	}

	public function testTargetPhpVersion(): void {
		$this->assertSame( '7.4', $this->ruleSet->targetPhpVersion() );
	}

	public function testRulesExcludePhp8Features(): void {
		$rules = $this->ruleSet->rules();
		$this->assertNotContains( ClassPropertyAssignToConstructorPromotionRector::class, $rules );
	}

	public function testRulesContainCommonRules(): void {
		$rules = $this->ruleSet->rules();
		$this->assertContains( AddVoidReturnTypeWhereNoReturnRector::class, $rules );
	}

	public function testSkipContainsPhp8Rectors(): void {
		$skip = $this->ruleSet->skip();
		$this->assertContains( ClassPropertyAssignToConstructorPromotionRector::class, $skip );
		$this->assertContains( ChangeSwitchToMatchRector::class, $skip );
	}
}
