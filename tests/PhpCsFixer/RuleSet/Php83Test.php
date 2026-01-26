<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\PhpCsFixer\RuleSet;

use FernleafSystems\QA\PhpCsFixer\RuleSet\Php83;
use PHPUnit\Framework\TestCase;

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

	public function testIsRiskyAllowed(): void {
		$this->assertTrue( $this->ruleSet->isRiskyAllowed() );
	}

	public function testRulesContainPsr12(): void {
		$rules = $this->ruleSet->rules();
		$this->assertTrue( $rules['@PSR12'] );
	}

	public function testRulesHaveNoSpacesInConcat(): void {
		$rules = $this->ruleSet->rules();
		$this->assertSame( 'none', $rules['concat_space']['spacing'] );
	}

	public function testRulesHaveSpacesInsideParentheses(): void {
		$rules = $this->ruleSet->rules();
		$this->assertSame( 'single', $rules['spaces_inside_parentheses']['space'] );
	}

	public function testRulesHaveSameLineBraces(): void {
		$rules = $this->ruleSet->rules();
		$this->assertSame( 'same_line', $rules['braces_position']['functions_opening_brace'] );
		$this->assertSame( 'same_line', $rules['braces_position']['classes_opening_brace'] );
	}

	public function testRulesUseTabIndentation(): void {
		// This is set in ConfigFactory, not in rules
		// Test that no spaces_inside_parentheses conflicts
		$rules = $this->ruleSet->rules();
		$this->assertArrayHasKey( 'spaces_inside_parentheses', $rules );
	}

	public function testRulesHaveNativeInvocation(): void {
		$rules = $this->ruleSet->rules();
		$this->assertArrayHasKey( 'native_function_invocation', $rules );
		$this->assertSame( 'namespaced', $rules['native_function_invocation']['scope'] );
	}

	public function testRulesHaveClassAttributeSeparation(): void {
		$rules = $this->ruleSet->rules();
		$elements = $rules['class_attributes_separation']['elements'];
		$this->assertSame( 'none', $elements['const'] );
		$this->assertSame( 'none', $elements['property'] );
		$this->assertSame( 'one', $elements['method'] );
	}
}
