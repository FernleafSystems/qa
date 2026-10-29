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

		$limits = HookLimits::fromProcess( $action->getOptions()->getAll() );
		if ( !$limits->allowExternalTools ) {
			$this->assertToolsInCheckout( (string)\getcwd() );
		}
		$process = new BoundedProcess( $limits );

		// Disable Rector parallel mode to avoid conflicts with file staging, and so one process does all the work
		\putenv( 'RECTOR_DISABLE_PARALLEL=1' );

		// Capture file checksums before running formatters
		$originalChecksums = $this->getFileChecksums( $stagedFiles );

		// Process files in batches to avoid command line length limits on Windows
		$batches = $this->batchFiles( $stagedFiles );
		$totalBatches = \count( $batches );

		foreach ( $batches as $batchIndex => $batch ) {
			$batchLabel = $totalBatches > 1 ? sprintf( ' (batch %d/%d)', $batchIndex + 1, $totalBatches ) : '';

			// Run Rector
			$io->write( "Running Rector{$batchLabel}..." );
			if ( $process->run( 'Rector', [ ...$this->tool( 'rector' ), 'process', ...$batch ] ) !== 0 ) {
				throw new \RuntimeException( 'Rector check failed' );
			}

			// Run PHP-CS-Fixer (must specify config when passing multiple paths); sequential, so one process does all the work
			$io->write( "Running PHP-CS-Fixer{$batchLabel}..." );
			if ( $process->run( 'PHP-CS-Fixer', [ ...$this->tool( 'php-cs-fixer' ), 'fix', '--config=.php-cs-fixer.php', '--allow-risky=yes', '--sequential', ...$batch ] ) !== 0 ) {
				throw new \RuntimeException( 'PHP-CS-Fixer check failed' );
			}
		}

		// Check if any files were modified by comparing checksums instead of git diff
		$newChecksums = $this->getFileChecksums( $stagedFiles );
		$modifiedFiles = [];
		foreach ( $stagedFiles as $file ) {
			if ( $originalChecksums[$file] !== $newChecksums[$file] ) {
				$modifiedFiles[] = $file;
			}
		}

		if ( !empty( $modifiedFiles ) ) {
			throw new \RuntimeException(
				'Code was reformatted in: '.\implode( ', ', $modifiedFiles ).'. Please stage the changes and commit again.'
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

	/**
	 * The command that runs a vendor tool: Composer's PHP proxy for it, run by this PHP, without a shell.
	 *
	 * @return non-empty-list<string>
	 */
	private function tool( string $name ): array {
		return [ \PHP_BINARY, \getcwd().'/vendor/bin/'.$name ];
	}

	/**
	 * Refuses to run when any vendor package lives outside this checkout, such as through a junction or symlink to
	 * another checkout's vendor directory. The tools would then load that checkout's code, not this one's: Rector,
	 * for one, has looped endlessly, allocating memory, when the two disagreed. allow_external_tools (or
	 * QA_HOOK_ALLOW_EXTERNAL_TOOLS) permits it.
	 */
	private function assertToolsInCheckout( string $checkout ): void {
		$root = \realpath( $checkout );
		if ( $root === false ) {
			return;
		}
		foreach ( \glob( $checkout.'/vendor/*/*', \GLOB_ONLYDIR ) ?: [] as $directory ) {
			$path = \realpath( $directory );
			if ( $path !== false && !self::isWithin( $path, $root ) ) {
				$package = \substr( $directory, \strlen( $checkout ) + 1 );
				throw new \RuntimeException( "{$package} resolves outside this checkout, to {$path}, so the tools would run another checkout's code. "
					.'Give this checkout its own vendor directory (composer install), or set allow_external_tools or '
					.HookLimits::ENV_ALLOW_EXTERNAL_TOOLS.'=1 to run them anyway.' );
			}
		}
	}

	private static function isWithin( string $path, string $root ): bool {
		$prefix = \rtrim( $root, '\\/' ).\DIRECTORY_SEPARATOR;
		return \PHP_OS_FAMILY === 'Windows' ? \str_starts_with( \strtolower( $path ), \strtolower( $prefix ) ) : \str_starts_with( $path, $prefix );
	}

	/**
	 * Get checksums for files to detect changes made by formatters.
	 *
	 * @param array<string> $files
	 * @return array<string, string>
	 */
	private function getFileChecksums( array $files ): array {
		$checksums = [];
		foreach ( $files as $file ) {
			if ( \file_exists( $file ) ) {
				$checksums[$file] = \md5_file( $file );
			}
			else {
				// File might be deleted (shouldn't happen with staged files, but be safe)
				$checksums[$file] = '';
			}
		}
		return $checksums;
	}
}
