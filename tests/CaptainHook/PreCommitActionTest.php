<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\CaptainHook;

use FernleafSystems\QA\CaptainHook\PreCommitAction;
use PHPUnit\Framework\TestCase;

final class PreCommitActionTest extends TestCase {
	/**
	 * Test that a small number of files returns a single batch.
	 */
	public function testBatchFilesSingleBatch(): void {
		$files = [
			'src/Foo.php',
			'src/Bar.php',
			'src/Baz.php',
		];

		$batches = $this->invokeBatchFiles( $files );

		$this->assertCount( 1, $batches );
		$this->assertSame( $files, $batches[0] );
	}

	/**
	 * Test that many long file paths are split into multiple batches.
	 */
	public function testBatchFilesMultipleBatches(): void {
		// Create 150 files with long paths (~100 chars each when escaped)
		// Total: 150 * 100 = 15000 chars, well over 7000 limit
		$files = [];
		for ( $i = 0; $i < 150; $i++ ) {
			$files[] = sprintf(
				'src/Very/Long/Directory/Structure/That/Takes/Up/Lots/Of/Space/In/Command/Line/File%03d.php',
				$i
			);
		}

		$batches = $this->invokeBatchFiles( $files );

		// Should have multiple batches (150 files * ~100 chars = 15000 chars, limit is ~6800)
		$this->assertGreaterThan( 1, \count( $batches ), 'Expected multiple batches for 150 long file paths' );

		// All files should be present across all batches
		$allFiles = \array_merge( ...$batches );
		$this->assertCount( 150, $allFiles );
		$this->assertSame( $files, $allFiles );
	}

	/**
	 * Test that batch sizes respect the command line limit.
	 */
	public function testBatchFilesRespectsCommandLineLimit(): void {
		// Create files with paths that will force batching
		$files = [];
		for ( $i = 0; $i < 200; $i++ ) {
			$files[] = sprintf(
				'src/Module/SubModule/Component/Service/File%03d.php',
				$i
			);
		}

		$batches = $this->invokeBatchFiles( $files );

		// Each batch's command line length should be under the limit
		$maxCmdLength = 7000; // Same as PreCommitAction::MAX_CMD_LENGTH
		$baseLength = 200;

		foreach ( $batches as $index => $batch ) {
			$escapedFiles = \array_map( escapeshellarg( ... ), $batch );
			$filesArg = \implode( ' ', $escapedFiles );
			$totalLength = \strlen( $filesArg ) + $baseLength;

			$this->assertLessThanOrEqual(
				$maxCmdLength,
				$totalLength,
				"Batch {$index} exceeds command line limit: {$totalLength} chars"
			);
		}
	}

	/**
	 * Test with empty file list.
	 */
	public function testBatchFilesEmptyList(): void {
		$batches = $this->invokeBatchFiles( [] );

		$this->assertSame( [], $batches );
	}

	/**
	 * Test with a single file.
	 */
	public function testBatchFilesSingleFile(): void {
		$files = ['src/SingleFile.php'];

		$batches = $this->invokeBatchFiles( $files );

		$this->assertCount( 1, $batches );
		$this->assertSame( $files, $batches[0] );
	}

	/**
	 * Test that files with spaces in paths are handled correctly.
	 */
	public function testBatchFilesWithSpacesInPaths(): void {
		$files = [
			'src/My Module/File One.php',
			'src/My Module/File Two.php',
			'src/Another Directory/With Spaces/File.php',
		];

		$batches = $this->invokeBatchFiles( $files );

		$this->assertCount( 1, $batches );
		$this->assertSame( $files, $batches[0] );
	}

	/**
	 * Invoke the private batchFiles method using reflection.
	 *
	 * @param array<string> $files
	 * @return array<array<string>>
	 */
	private function invokeBatchFiles( array $files ): array {
		$action = new PreCommitAction();
		$method = new \ReflectionMethod( PreCommitAction::class, 'batchFiles' );

		return $method->invoke( $action, $files );
	}
}
