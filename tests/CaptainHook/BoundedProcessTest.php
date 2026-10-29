<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\CaptainHook;

use FernleafSystems\QA\CaptainHook\BoundedProcess;
use FernleafSystems\QA\CaptainHook\HookLimits;
use FernleafSystems\QA\CaptainHook\ToolLimitExceeded;
use PHPUnit\Framework\TestCase;

/** Real child PHP processes: one that finishes, one that hangs, one whose memory runs away. */
final class BoundedProcessTest extends TestCase {
	public function testReturnsTheToolsExitCode(): void {
		$this->assertSame( 3, ( new BoundedProcess( new HookLimits() ) )->run( 'Tool', self::php( 'exit(3);' ) ) );
	}

	public function testStopsAToolThatRunsPastTheTimeout(): void {
		$started = \microtime( true );
		try {
			( new BoundedProcess( new HookLimits( timeoutSeconds: 1 ) ) )->run( 'Tool', self::php( 'sleep(60);' ) );
			$this->fail( 'The tool was not stopped.' );
		}
		catch ( ToolLimitExceeded $exception ) {
			$this->assertStringContainsString( 'Tool was stopped: it ran longer than 1 seconds', $exception->getMessage() );
		}
		$this->assertLessThan( 15, \microtime( true ) - $started );
	}

	public function testStopsAToolWhoseMemoryRunsAwayWhateverItsMemoryLimit(): void {
		$started = \microtime( true );
		try {
			// memory_limit=-1, as for a tool whose allocations PHP does not bound.
			( new BoundedProcess( new HookLimits( maxMemoryMb: 128, timeoutSeconds: 60 ) ) )->run( 'Tool', self::php(
				'$a = []; for ( $i = 0; $i < 40; $i++ ) { $a[] = str_repeat( "x", 10485760 ); } sleep( 60 );',
				'-1'
			) );
			$this->fail( 'The tool was not stopped.' );
		}
		catch ( ToolLimitExceeded $exception ) {
			$this->assertMatchesRegularExpression( '/Tool was stopped: it used \d+ MB, over the 128 MB limit/', $exception->getMessage() );
		}
		$this->assertLessThan( 30, \microtime( true ) - $started );
	}

	public function testLimitsOfZeroAreOff(): void {
		$this->assertSame( 0, ( new BoundedProcess( new HookLimits( maxMemoryMb: 0, timeoutSeconds: 0 ) ) )->run( 'Tool', self::php( 'usleep(300000);' ) ) );
	}

	public function testReadsAProcessesMemory(): void {
		$this->assertGreaterThan( 0, BoundedProcess::memoryMb( \getmypid() ) );
	}

	/** @return non-empty-list<string> */
	private static function php( string $code, string $memoryLimit = '128M' ): array {
		return [ \PHP_BINARY, '-n', '-d', 'memory_limit='.$memoryLimit, '-r', $code ];
	}
}
