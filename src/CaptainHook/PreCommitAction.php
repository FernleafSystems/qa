<?php declare( strict_types=1 );

namespace FernleafSystems\QA\CaptainHook;

use CaptainHook\App\Config;
use CaptainHook\App\Console\IO;
use CaptainHook\App\Hook\Action;
use SebastianFeldmann\Git\Repository;

/**
 * Pre-commit action that runs Rector and PHP-CS-Fixer on staged files.
 * Based on FernleafSystems Auto-AI-Translations pre-commit script.
 */
class PreCommitAction implements Action {
	/**
	 * Maximum command line length (Windows limit is ~8191, use conservative value).
	 */
	private const int MAX_CMD_LENGTH = 7000;

	public function execute( Config $config, IO $io, Repository $repository, Config\Action $action ): void {
		$stagedFiles = $this->getStagedPhpFiles( $repository );

		if ( empty( $stagedFiles ) ) {
			$io->write( 'No PHP files staged for commit.' );
			return;
		}

		// Disable Rector parallel mode to avoid conflicts with file staging
		\putenv( 'RECTOR_DISABLE_PARALLEL=1' );

		$rectorBinary = $this->getBinaryPath( 'rector' );
		$csFixerBinary = $this->getBinaryPath( 'php-cs-fixer' );

		// Process files in batches to avoid command line length limits on Windows
		$batches = $this->batchFiles( $stagedFiles );
		$totalBatches = \count( $batches );

		foreach ( $batches as $batchIndex => $batch ) {
			$escapedFiles = \array_map( escapeshellarg( ... ), $batch );
			$filesArg = \implode( ' ', $escapedFiles );

			$batchLabel = $totalBatches > 1 ? sprintf( ' (batch %d/%d)', $batchIndex + 1, $totalBatches ) : '';

			// Run Rector
			$io->write( "Running Rector{$batchLabel}..." );
			$rectorExit = $this->runCommand( $rectorBinary.' process '.$filesArg );
			if ( $rectorExit !== 0 ) {
				throw new \RuntimeException( 'Rector check failed' );
			}

			// Run PHP-CS-Fixer (must specify config when passing multiple paths)
			$io->write( "Running PHP-CS-Fixer{$batchLabel}..." );
			$csExit = $this->runCommand( $csFixerBinary.' fix --config=.php-cs-fixer.php --allow-risky=yes '.$filesArg );
			if ( $csExit !== 0 ) {
				throw new \RuntimeException( 'PHP-CS-Fixer check failed' );
			}
		}

		// Check if any files were modified (check all files at once using batches)
		$filesModified = false;
		foreach ( $batches as $batch ) {
			$escapedFiles = \array_map( escapeshellarg( ... ), $batch );
			$filesArg = \implode( ' ', $escapedFiles );
			$diffExit = $this->runCommand( 'git diff --quiet -- '.$filesArg, false );
			if ( $diffExit !== 0 ) {
				$filesModified = true;
				break;
			}
		}

		if ( $filesModified ) {
			throw new \RuntimeException(
				'Code was reformatted. Please stage the changes and commit again.'
			);
		}

		$io->write( '<info>Code quality checks passed</info>' );
	}

	/**
	 * Split files into batches that won't exceed command line length limits.
	 *
	 * @param array<string> $files
	 * @return array<array<string>>
	 */
	private function batchFiles( array $files ): array {
		$batches = [];
		$currentBatch = [];
		$currentLength = 0;

		// Base command length estimate (binary path + flags)
		$baseLength = 200;

		foreach ( $files as $file ) {
			$escapedLength = \strlen( \escapeshellarg( $file ) ) + 1; // +1 for space

			if ( $currentLength + $escapedLength + $baseLength > self::MAX_CMD_LENGTH && !empty( $currentBatch ) ) {
				$batches[] = $currentBatch;
				$currentBatch = [];
				$currentLength = 0;
			}

			$currentBatch[] = $file;
			$currentLength += $escapedLength;
		}

		if ( !empty( $currentBatch ) ) {
			$batches[] = $currentBatch;
		}

		return $batches;
	}

	/**
	 * @return array<string>
	 */
	private function getStagedPhpFiles( Repository $repository ): array {
		$stagedFiles = $repository->getIndexOperator()->getStagedFiles();

		return \array_values( \array_filter(
			$stagedFiles,
			static fn( string $file ): bool => \preg_match( '/\.php$/i', $file ) === 1
		) );
	}

	private function runCommand( string $command, bool $printOutput = true ): int {
		$exitCode = 0;

		if ( $printOutput ) {
			\passthru( $command, $exitCode );
		}
		else {
			\exec( $command, $output, $exitCode );
		}

		return $exitCode;
	}

	private function getBinaryPath( string $name ): string {
		$binary = \getcwd().'/vendor/bin/'.$name;
		if ( \PHP_OS_FAMILY === 'Windows' ) {
			$binary = \str_replace( '/', '\\', $binary ).'.bat';
		}
		return \escapeshellarg( $binary );
	}
}
