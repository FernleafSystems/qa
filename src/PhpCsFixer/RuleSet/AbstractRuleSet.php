<?php declare( strict_types=1 );

namespace FernleafSystems\QA\PhpCsFixer\RuleSet;

abstract class AbstractRuleSet implements RuleSetInterface {
	public function isRiskyAllowed(): bool {
		return true;
	}

	/**
	 * Common rules shared across ALL PHP versions.
	 * Based on FernleafSystems coding standards.
	 *
	 * @return array<string, mixed>
	 */
	protected function commonRules(): array {
		return [
			// Base standard
			'@PSR12' => true,

			// === DECLARATIONS ===
			// Keep declare(strict_types=1) on same line as <?php when it exists
			'blank_line_after_opening_tag' => false,

			// === ARRAYS ===
			'array_syntax'                => ['syntax' => 'short'],
			'trailing_comma_in_multiline' => ['elements' => ['arrays']],

			// === STRING CONCATENATION ===
			// NO spaces around concatenation: 'string'.$variable
			'concat_space' => ['spacing' => 'none'],

			// === OPERATORS ===
			'binary_operator_spaces' => [
				'default'   => 'single_space',
				'operators' => [
					'=>' => 'align_single_space_minimal',
					'='  => 'single_space',
				],
			],
			'ternary_operator_spaces' => true,

			// === CONTROL STRUCTURES ===
			'elseif' => true,

			// === BRACES ===
			// Opening brace on same line for everything
			'braces_position' => [
				'functions_opening_brace'           => 'same_line',
				'classes_opening_brace'             => 'same_line',
				'control_structures_opening_brace'  => 'same_line',
				'anonymous_functions_opening_brace' => 'same_line',
				'anonymous_classes_opening_brace'   => 'same_line',
			],
			'control_structure_braces'                => true,
			'control_structure_continuation_position' => ['position' => 'next_line'],

			// === TYPE HINTS ===
			'return_type_declaration'                          => ['space_before' => 'none'],
			'nullable_type_declaration_for_default_null_value' => true,

			// === MODERN PHP ===
			'ternary_to_null_coalescing' => true,

			// === CLEANUP ===
			'no_unused_imports'                 => true,
			'single_blank_line_at_eof'          => false, // Disable (violates PSR-12)
			'no_trailing_whitespace'            => true,
			'no_trailing_whitespace_in_comment' => true,
			'no_extra_blank_lines'              => ['tokens' => ['extra']],

			// === VISIBILITY ===
			'visibility_required' => ['elements' => ['method', 'property', 'const']],

			// === CASTING ===
			'lowercase_cast'    => true,
			'short_scalar_cast' => true,

			// === SPACING ===
			// Spaces inside parentheses: env( 'KEY' ) instead of env('KEY')
			'spaces_inside_parentheses' => ['space' => 'single'],
			'function_declaration'      => ['closure_fn_spacing' => 'none'],

			// === PHPDOC ===
			'no_superfluous_phpdoc_tags' => true,
			'no_empty_phpdoc'            => true,
			'phpdoc_trim'                => true,

			// === CLASS MEMBER SEPARATION ===
			'class_attributes_separation' => [
				'elements' => [
					'const'        => 'none',
					'property'     => 'none',
					'method'       => 'one',
					'trait_import' => 'none',
					'case'         => 'none',
				],
			],

			// === MISC ===
			'single_quote'     => true,
			'no_closing_tag'   => true,
			'encoding'         => true,
			'full_opening_tag' => true,
		];
	}
}
