<?php declare( strict_types=1 );

namespace FernleafSystems\QA\PhpCsFixer\RuleSet;

interface RuleSetInterface {
	public function targetPhpVersion(): string;

	/**
	 * @return array<string, mixed>
	 */
	public function rules(): array;

	public function isRiskyAllowed(): bool;
}
