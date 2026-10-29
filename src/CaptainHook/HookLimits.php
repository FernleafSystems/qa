<?php declare( strict_types=1 );

namespace FernleafSystems\QA\CaptainHook;

/**
 * Bounds on each tool the pre-commit hook runs, so a tool that runs away is stopped instead of exhausting the
 * machine. The defaults only catch a runaway; a normal run uses a fraction of them.
 *
 * Each can be set in the hook action's options in captainhook.json, and overridden for one run by an environment
 * variable. 0 turns a limit off.
 */
final readonly class HookLimits {
	public const int DEFAULT_MAX_MEMORY_MB = 8192;
	public const int DEFAULT_TIMEOUT_SECONDS = 1800;

	/** Environment variables, which override the action's options. */
	public const string ENV_MAX_MEMORY_MB = 'QA_HOOK_MAX_MEMORY_MB';

	public const string ENV_TIMEOUT_SECONDS = 'QA_HOOK_TIMEOUT_SECONDS';
	public const string ENV_ALLOW_EXTERNAL_TOOLS = 'QA_HOOK_ALLOW_EXTERNAL_TOOLS';

	public function __construct(
		public int $maxMemoryMb = self::DEFAULT_MAX_MEMORY_MB,
		public int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
		public bool $allowExternalTools = false,
	) {
	}

	/**
	 * @param array<string, mixed> $options The hook action's options: max_memory_mb, timeout_seconds, allow_external_tools
	 * @param array<string, string|false> $environment Environment variable values, false when unset
	 */
	public static function from( array $options, array $environment ): self {
		return new self(
			self::limit( $environment[self::ENV_MAX_MEMORY_MB] ?? false, $options['max_memory_mb'] ?? null, self::DEFAULT_MAX_MEMORY_MB, 'max_memory_mb' ),
			self::limit( $environment[self::ENV_TIMEOUT_SECONDS] ?? false, $options['timeout_seconds'] ?? null, self::DEFAULT_TIMEOUT_SECONDS, 'timeout_seconds' ),
			self::flag( $environment[self::ENV_ALLOW_EXTERNAL_TOOLS] ?? false, $options['allow_external_tools'] ?? false ),
		);
	}

	public static function fromProcess( array $options ): self {
		return self::from( $options, [
			self::ENV_MAX_MEMORY_MB        => \getenv( self::ENV_MAX_MEMORY_MB ),
			self::ENV_TIMEOUT_SECONDS      => \getenv( self::ENV_TIMEOUT_SECONDS ),
			self::ENV_ALLOW_EXTERNAL_TOOLS => \getenv( self::ENV_ALLOW_EXTERNAL_TOOLS ),
		] );
	}

	private static function limit( string|false $environment, mixed $option, int $default, string $name ): int {
		$value = $environment !== false && $environment !== '' ? $environment : $option;
		if ( $value === null ) {
			return $default;
		}
		// Only a number or a numeric string: a JSON true would otherwise read as a limit of 1.
		$limit = \is_int( $value ) || \is_string( $value ) ? \filter_var( $value, \FILTER_VALIDATE_INT, [ 'options' => [ 'min_range' => 0 ] ] ) : false;
		if ( $limit === false ) {
			throw new \InvalidArgumentException( "The pre-commit hook's {$name} must be a whole number of 0 or more; 0 turns it off." );
		}
		return $limit;
	}

	private static function flag( string|false $environment, mixed $option ): bool {
		$value = $environment !== false && $environment !== '' ? $environment : $option;
		$flag = \is_bool( $value ) || \is_int( $value ) || \is_string( $value ) ? \filter_var( $value, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE ) : null;
		if ( $flag === null ) {
			throw new \InvalidArgumentException( "The pre-commit hook's allow_external_tools must be true or false." );
		}
		return $flag;
	}
}
