<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector\RuleSet;

interface RuleSetInterface {
	public function targetPhpVersion(): string;

	/**
	 * @return array<class-string>
	 */
	public function rules(): array;

	/**
	 * @return array<class-string|string>
	 */
	public function skip(): array;

	public function usePhpSets(): bool;
}
