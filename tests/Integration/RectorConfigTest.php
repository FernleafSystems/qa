<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests that actually run Rector to verify configs work.
 */
final class RectorConfigTest extends TestCase {
	private function getRectorBinary(): string {
		$binary = \dirname( __DIR__, 2 ).'/vendor/bin/rector';
		if ( \PHP_OS_FAMILY === 'Windows' ) {
			$binary .= '.bat';
		}
		return $binary;
	}

	public function testRectorConfigLoadsWithoutError(): void {
		$output = [];
		$exitCode = 0;

		$binary = $this->getRectorBinary();
		$projectRoot = \dirname( __DIR__, 2 );

		// Run rector --dry-run on a single file to verify config loads
		\exec( "\"{$binary}\" process --dry-run --config=\"{$projectRoot}/rector.php\" \"{$projectRoot}/src/PhpVersionDetector.php\" 2>&1", $output, $exitCode );

		$outputStr = \implode( "\n", $output );

		// Exit code 0 = no changes, 2 = changes would be made - both are valid
		// Any other exit code indicates an error
		$this->assertTrue(
			\in_array( $exitCode, [0, 2], true ),
			"Rector failed with exit code {$exitCode}. Output: {$outputStr}"
		);
	}

	public function testRectorConfigHandlesMultipleFiles(): void {
		$output = [];
		$exitCode = 0;

		$binary = $this->getRectorBinary();
		$projectRoot = \dirname( __DIR__, 2 );

		// Run rector on multiple files
		\exec( "\"{$binary}\" process --dry-run --config=\"{$projectRoot}/rector.php\" \"{$projectRoot}/src/PhpVersionDetector.php\" \"{$projectRoot}/src/Installer.php\" 2>&1", $output, $exitCode );

		$outputStr = \implode( "\n", $output );

		$this->assertTrue(
			\in_array( $exitCode, [0, 2], true ),
			"Rector failed with multiple files. Exit code {$exitCode}. Output: {$outputStr}"
		);
	}
}
