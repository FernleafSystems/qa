<?php declare( strict_types=1 );

namespace FernleafSystems\QA\CaptainHook;

/**
 * Runs one tool as its own process, without a shell, and stops it if it passes the hook's memory or time limit.
 * PHP's memory_limit does not bound everything a PHP tool can allocate, so memory is read from the operating
 * system: the process's working set on Windows, its resident set elsewhere.
 */
final readonly class BoundedProcess {
	private const int POLL_MICROSECONDS = 250_000;

	public function __construct( private HookLimits $limits ) {
	}

	/**
	 * @param non-empty-list<string> $command The program and its arguments
	 * @return int The tool's exit code
	 * @throws ToolLimitExceeded When the tool passes a limit; it has been stopped
	 */
	public function run( string $label, array $command ): int {
		$process = \proc_open( $command, [ 0 => [ 'pipe', 'r' ], 1 => \STDOUT, 2 => \STDERR ], $pipes );
		if ( !\is_resource( $process ) ) {
			throw new \RuntimeException( "Could not start {$label}." );
		}
		\fclose( $pipes[0] );
		$started = \microtime( true );
		while ( true ) {
			$status = \proc_get_status( $process );
			if ( !$status['running'] ) {
				\proc_close( $process );
				return $status['exitcode'];
			}
			$exceeded = $this->exceeded( $status['pid'], \microtime( true ) - $started );
			if ( $exceeded !== null ) {
				self::stop( $process, $status['pid'] );
				throw new ToolLimitExceeded( "{$label} was stopped: {$exceeded}" );
			}
			\usleep( self::POLL_MICROSECONDS );
		}
	}

	private function exceeded( int $pid, float $elapsed ): ?string {
		if ( $this->limits->timeoutSeconds > 0 && $elapsed > $this->limits->timeoutSeconds ) {
			return "it ran longer than {$this->limits->timeoutSeconds} seconds (timeout_seconds, ".HookLimits::ENV_TIMEOUT_SECONDS.').';
		}
		if ( $this->limits->maxMemoryMb > 0 ) {
			$memoryMb = self::memoryMb( $pid );
			if ( $memoryMb !== null && $memoryMb > $this->limits->maxMemoryMb ) {
				return "it used {$memoryMb} MB, over the {$this->limits->maxMemoryMb} MB limit (max_memory_mb, ".HookLimits::ENV_MAX_MEMORY_MB.').';
			}
		}
		return null;
	}

	/**
	 * The tool's resident memory in MB (its working set on Windows), or null when it cannot be read, such as just
	 * after it exits. On Linux and macOS this includes every process it started; on Windows it is the tool's own,
	 * which suffices because the hook runs each tool as one process.
	 */
	public static function memoryMb( int $pid ): ?int {
		if ( \PHP_OS_FAMILY === 'Windows' ) {
			$line = (string)\exec( 'tasklist /FI "PID eq '.$pid.'" /FO CSV /NH' );
			// "php.exe","1234","Console","1","25,964 K": the last field, in KB, with locale separators.
			$fields = \str_getcsv( $line, escape: '' );
			$kb = \count( $fields ) >= 5 ? \preg_replace( '/\D/', '', (string)\end( $fields ) ) : '';
			return $kb === '' ? null : \intdiv( (int)$kb, 1024 );
		}
		$tree = self::tree( $pid );
		return $tree === [] ? null : \intdiv( \array_sum( $tree ), 1024 );
	}

	/**
	 * The process and everything it started, from one `ps` listing (Linux and macOS).
	 *
	 * @return array<int, int> Resident memory in KB by process id
	 */
	private static function tree( int $pid ): array {
		\exec( 'ps -A -o pid=,ppid=,rss=', $lines );
		$children = [];
		$memory = [];
		foreach ( $lines as $line ) {
			if ( \preg_match( '/^\s*(\d+)\s+(\d+)\s+(\d+)\s*$/', $line, $match ) === 1 ) {
				$children[(int)$match[2]][] = (int)$match[1];
				$memory[(int)$match[1]] = (int)$match[3];
			}
		}
		if ( !isset( $memory[$pid] ) ) {
			return [];
		}
		$tree = [];
		for ( $queue = [ $pid ]; $queue !== []; ) {
			$next = \array_shift( $queue );
			$tree[$next] = $memory[$next] ?? 0;
			\array_push( $queue, ...( $children[$next] ?? [] ) );
		}
		return $tree;
	}

	/**
	 * Stops the tool and everything it started.
	 *
	 * @param resource $process
	 */
	private static function stop( $process, int $pid ): void {
		if ( \PHP_OS_FAMILY === 'Windows' ) {
			\exec( 'taskkill /F /T /PID '.$pid.' 2>NUL' );
		}
		else {
			$started = \array_diff( \array_keys( self::tree( $pid ) ), [ $pid ] );
			if ( $started !== [] ) {
				\exec( 'kill -9 '.\implode( ' ', $started ).' 2>/dev/null' );
			}
			\proc_terminate( $process, 9 );
		}
		\proc_close( $process );
	}
}
