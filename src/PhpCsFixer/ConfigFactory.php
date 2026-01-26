<?php declare( strict_types=1 );

namespace FernleafSystems\QA\PhpCsFixer;

use FernleafSystems\QA\PhpCsFixer\RuleSet\RuleSetInterface;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

final class ConfigFactory {
	/**
	 * @var Finder|null
	 */
	private $finder;

	/**
	 * @var array<string>
	 */
	private $paths = [];

	/**
	 * @var array<string>
	 */
	private $excludedPaths = ['vendor'];

	/**
	 * @var array<string, mixed>
	 */
	private $customRules = [];

	private function __construct( private RuleSetInterface $ruleSet ) {
	}

	public static function fromRuleSet( RuleSetInterface $ruleSet ): self {
		return new self( $ruleSet );
	}

	/**
	 * @param array<string> $paths
	 */
	public function withPaths( array $paths ): self {
		$clone = clone $this;
		$clone->paths = $paths;
		return $clone;
	}

	/**
	 * @param array<string> $paths
	 */
	public function withExcludedPaths( array $paths ): self {
		$clone = clone $this;
		$clone->excludedPaths = $paths;
		return $clone;
	}

	/**
	 * @param array<string, mixed> $rules
	 */
	public function withCustomRules( array $rules ): self {
		$clone = clone $this;
		$clone->customRules = $rules;
		return $clone;
	}

	public function withFinder( Finder $finder ): self {
		$clone = clone $this;
		$clone->finder = $finder;
		return $clone;
	}

	public function create(): Config {
		$finder = $this->finder ?? $this->createDefaultFinder();

		$rules = \array_merge( $this->ruleSet->rules(), $this->customRules );

		return ( new Config() )
			->setRiskyAllowed( $this->ruleSet->isRiskyAllowed() )
			->setRules( $rules )
			->setIndent( "\t" )
			->setLineEnding( "\n" )
			->setFinder( $finder );
	}

	private function createDefaultFinder(): Finder {
		$finder = Finder::create();

		if ( !empty( $this->paths ) ) {
			$finder->in( $this->paths );
		}

		foreach ( $this->excludedPaths as $path ) {
			$finder->exclude( $path );
		}

		return $finder;
	}
}
