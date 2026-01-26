<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\PhpCsFixer\RuleSet;

use FernleafSystems\QA\PhpCsFixer\RuleSet\Php74;
use PHPUnit\Framework\TestCase;

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

	public function testRulesContainPsr12(): void {
		$rules = $this->ruleSet->rules();
		$this->assertTrue( $rules['@PSR12'] );
	}

	public function testRulesHaveNoSpacesInConcat(): void {
		$rules = $this->ruleSet->rules();
		$this->assertSame( 'none', $rules['concat_space']['spacing'] );
	}

	public function testTrailingCommaOnlyInArrays(): void {
		$rules = $this->ruleSet->rules();
		// PHP 7.4 doesn't support trailing comma in function parameters
		$this->assertSame( ['arrays'], $rules['trailing_comma_in_multiline']['elements'] );
	}
}
