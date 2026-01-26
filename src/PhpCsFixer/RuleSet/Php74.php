<?php declare( strict_types=1 );

namespace FernleafSystems\QA\PhpCsFixer\RuleSet;

final class Php74 extends AbstractRuleSet {
	public function targetPhpVersion(): string {
		return '7.4';
	}

	public function rules(): array {
		return \array_merge(
			$this->commonRules(),
			[
				// === GLOBAL NAMESPACE ===
				// Add leading backslash to native functions (PHP 7.4 compatible)
				'native_function_invocation' => [
					'include' => ['@all'],
					'exclude' => ['sprintf', 'error_log', 'var_dump', 'var_export'],
					'scope'   => 'namespaced',
					'strict'  => true,
				],

				// Disable PHP 8.0+ features
				'trailing_comma_in_multiline' => [
					'elements' => ['arrays'], // No trailing comma in function params for 7.4
				],
			]
		);
	}
}
