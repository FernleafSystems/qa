<?php declare( strict_types=1 );

namespace FernleafSystems\QA\PhpCsFixer\RuleSet;

class Php83 extends AbstractRuleSet {
	public function targetPhpVersion(): string {
		return '8.3';
	}

	public function rules(): array {
		return \array_merge(
			$this->commonRules(),
			[
				// === GLOBAL NAMESPACE ===
				'native_constant_invocation' => [
					'scope'        => 'namespaced',
					'strict'       => true,
					'fix_built_in' => true,
					'include'      => [],
					'exclude'      => ['empty', 'null', 'false', 'true'],
				],
				'native_function_invocation' => [
					'include' => ['@all'],
					'exclude' => ['sprintf', 'error_log', 'var_dump', 'var_export'],
					'scope'   => 'namespaced',
					'strict'  => true,
				],
				'global_namespace_import' => [
					'import_classes'   => false,
					'import_constants' => false,
					'import_functions' => false,
				],
			]
		);
	}
}
