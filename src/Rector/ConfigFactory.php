<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector;

use FernleafSystems\QA\Rector\RuleSet\RuleSetInterface;
use Rector\Config\RectorConfig;

final class ConfigFactory {
	private RuleSetInterface $ruleSet;

	/**
	 * @var array<string>
	 */
	private $paths = [];

	/**
	 * @var array<class-string|string>
	 */
	private $additionalSkip = [];

	/**
	 * @var bool
	 */
	private $importShortClasses = false;

	/**
	 * @var bool
	 */
	private $removeUnusedImports = true;

	private function __construct( RuleSetInterface $ruleSet ) {
		$this->ruleSet = $ruleSet;
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
	 * @param array<class-string|string> $skip
	 */
	public function withSkip( array $skip ): self {
		$clone = clone $this;
		$clone->additionalSkip = $skip;
		return $clone;
	}

	public function configure( RectorConfig $config ): RectorConfig {
		if ( !empty( $this->paths ) ) {
			$config->paths( $this->paths );
		}

		// Note: PHP version sets (withPhpSets) must be configured in the generated
		// rector.php file directly because they use named arguments (PHP 8.0+).
		// This factory focuses on rules, skip lists, and import configuration.

		// Apply rules
		$config->rules( $this->ruleSet->rules() );

		// Apply skip list
		$allSkip = \array_merge( $this->ruleSet->skip(), $this->additionalSkip );
		if ( !empty( $allSkip ) ) {
			$config->skip( $allSkip );
		}

		// Import configuration (from your projects)
		$config->importNames();
		$config->importShortClasses( $this->importShortClasses );
		if ( $this->removeUnusedImports ) {
			$config->removeUnusedImports();
		}

		return $config;
	}
}
