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
	public function execute( Config $config, IO $io, Repository $repository, Config\Action $action ): void {
		$stagedFiles = $this->getStagedPhpFiles( $repository );

		if ( empty( $stagedFiles ) ) {
			$io->write( 'No PHP files staged for commit.' );
			return;
		}

		// Disable Rector parallel mode to avoid conflicts with file staging
		\putenv( 'RECTOR_DISABLE_PARALLEL=1' );

		$escapedFiles = \array_map( escapeshellarg( ... ), $stagedFiles );
		$filesArg = \implode( ' ', $escapedFiles );

		// Run Rector
		$io->write( 'Running Rector...' );
		$rectorBinary = $this->getBinaryPath( 'rector' );
		$rectorExit = $this->runCommand( $rectorBinary.' process '.$filesArg );
		if ( $rectorExit !== 0 ) {
			throw new \RuntimeException( 'Rector check failed' );
		}

		// Run PHP-CS-Fixer (must specify config when passing multiple paths)
		$io->write( 'Running PHP-CS-Fixer...' );
		$csFixerBinary = $this->getBinaryPath( 'php-cs-fixer' );
		$csExit = $this->runCommand( $csFixerBinary.' fix --config=.php-cs-fixer.php --allow-risky=yes '.$filesArg );
		if ( $csExit !== 0 ) {
			throw new \RuntimeException( 'PHP-CS-Fixer check failed' );
		}

		// Check if files were modified
		$diffExit = $this->runCommand( 'git diff --quiet -- '.$filesArg, false );
		if ( $diffExit !== 0 ) {
			throw new \RuntimeException(
				'Code was reformatted. Please stage the changes and commit again.'
			);
		}

		$io->write( '<info>Code quality checks passed</info>' );
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
