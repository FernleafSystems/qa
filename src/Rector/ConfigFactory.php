<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Rector;

use FernleafSystems\QA\Rector\RuleSet\Php83;
use FernleafSystems\QA\Rector\RuleSet\Php84;
use FernleafSystems\QA\Rector\RuleSet\Php85;
use FernleafSystems\QA\Rector\RuleSet\RuleSetInterface;
use Rector\Config\RectorConfig;
use Rector\Configuration\RectorConfigBuilder;

final class ConfigFactory {
	/** @var array<string> */
	private array $paths = [];

	/** @var array<string> */
	private array $skip = [];

	private function __construct( private RuleSetInterface $ruleSet ) {
	}

	public static function fromRuleSet( RuleSetInterface $ruleSet ): self {
		return new self( $ruleSet );
	}

	/**
	 * Create config for a specific PHP version.
	 */
	public static function forPhpVersion( string $version ): self {
		$ruleSet = match ( $version ) {
			'8.4'   => new Php84(),
			'8.5'   => new Php85(),
			default => new Php83(),
		};
		return new self( $ruleSet );
	}

	/**
	 * @param array<string> $paths Absolute paths to scan
	 */
	public function withPaths( array $paths ): self {
		$clone = clone $this;
		$clone->paths = $paths;
		return $clone;
	}

	/**
	 * @param array<string> $skip Paths or rules to skip
	 */
	public function withSkip( array $skip ): self {
		$clone = clone $this;
		$clone->skip = $skip;
		return $clone;
	}

	/**
	 * Build and return the Rector configuration.
	 */
	public function create(): RectorConfigBuilder {
		$phpVersion = $this->ruleSet->targetPhpVersion();

		$config = RectorConfig::configure();

		if ( !empty( $this->paths ) ) {
			$config = $config->withPaths( $this->paths );
		}

		$allSkip = \array_merge( $this->ruleSet->skip(), $this->skip );
		if ( !empty( $allSkip ) ) {
			$config = $config->withSkip( $allSkip );
		}

		$config = $config->withRules( $this->ruleSet->rules() );

		// Apply PHP version sets
		$config = $this->applyPhpSets( $config, $phpVersion );

		$config = $config->withImportNames( false, true );

		// Handle parallel mode
		if ( (bool)\getenv( 'RECTOR_DISABLE_PARALLEL' ) ) {
			return $config->withoutParallel();
		}

		return $config->withParallel( 360, 4 );
	}

	private function applyPhpSets( RectorConfigBuilder $config, string $phpVersion ): RectorConfigBuilder {
		return match ( $phpVersion ) {
			'8.3'   => $config->withPhpSets( php83: true ),
			'8.4'   => $config->withPhpSets( php84: true ),
			'8.5'   => $config->withPhpSets( php85: true ),
			default => $config->withPhpSets( php83: true ),
		};
	}
}
