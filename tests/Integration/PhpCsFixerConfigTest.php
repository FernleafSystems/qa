<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Integration tests that actually run PHP CS Fixer to verify configs work.
 */
final class PhpCsFixerConfigTest extends TestCase {
	private function getPhpCsFixerBinary(): string {
		$binary = \dirname( __DIR__, 2 ).'/vendor/bin/php-cs-fixer';
		if ( \PHP_OS_FAMILY === 'Windows' ) {
			$binary .= '.bat';
		}
		return $binary;
	}

	public function testPhpCsFixerConfigLoadsWithoutError(): void {
		$output = [];
		$exitCode = 0;

		$binary = $this->getPhpCsFixerBinary();
		$projectRoot = \dirname( __DIR__, 2 );

		// Run php-cs-fixer --dry-run on a single file
		\exec( "\"{$binary}\" fix --dry-run --config=\"{$projectRoot}/.php-cs-fixer.php\" --allow-risky=yes \"{$projectRoot}/src/PhpVersionDetector.php\" 2>&1", $output, $exitCode );

		$outputStr = \implode( "\n", $output );

		// Exit code 0 = no changes, 8 = changes would be made - both are valid
		$this->assertTrue(
			\in_array( $exitCode, [0, 8], true ),
			"PHP CS Fixer failed with exit code {$exitCode}. Output: {$outputStr}"
		);
	}

	public function testPhpCsFixerConfigHandlesMultipleFiles(): void {
		$output = [];
		$exitCode = 0;

		$binary = $this->getPhpCsFixerBinary();
		$projectRoot = \dirname( __DIR__, 2 );

		// Run php-cs-fixer on multiple files - THIS is what was failing before
		\exec( "\"{$binary}\" fix --dry-run --config=\"{$projectRoot}/.php-cs-fixer.php\" --allow-risky=yes \"{$projectRoot}/src/PhpVersionDetector.php\" \"{$projectRoot}/src/Installer.php\" 2>&1", $output, $exitCode );

		$outputStr = \implode( "\n", $output );

		$this->assertTrue(
			\in_array( $exitCode, [0, 8], true ),
			"PHP CS Fixer failed with multiple files. Exit code {$exitCode}. Output: {$outputStr}"
		);
	}
}
