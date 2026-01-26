#!/usr/bin/env php
<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Bin;

/**
 * Shared pre-commit pipeline for Rector and PHP-CS-Fixer.
 * Based on FernleafSystems Auto-AI-Translations.
 *
 * Install: Add to .git/hooks/pre-commit or use with CaptainHook
 */

// Find project root by looking for composer.json
$projectRoot = findProjectRoot( __DIR__ );
if ( $projectRoot !== null ) {
	\chdir( $projectRoot );
}

function findProjectRoot( string $startDir ): ?string {
	$dir = $startDir;
	$maxLevels = 10; // Prevent infinite loop

	for ( $i = 0; $i < $maxLevels; $i++ ) {
		if ( \file_exists( $dir.'/composer.json' ) && !\file_exists( $dir.'/vendor' ) === false ) {
			// Found a composer.json with vendor directory = project root
			if ( \is_dir( $dir.'/vendor' ) ) {
				return $dir;
			}
		}
		$parent = \dirname( $dir );
		if ( $parent === $dir ) {
			break; // Reached filesystem root
		}
		$dir = $parent;
	}

	// Fallback: use current working directory
	return \getcwd() ?: null;
}

function runCommand( string $command, bool $printOutput = true ): int {
	$exitCode = 0;

	if ( $printOutput ) {
		\passthru( $command, $exitCode );
	}
	else {
		$output = [];
		\exec( $command, $output, $exitCode );
	}

	return $exitCode;
}

function gatherStagedFiles(): array {
	$output = [];
	\exec( 'git diff --cached --name-only --diff-filter=ACM', $output, $exitCode );
	if ( $exitCode !== 0 ) {
		return [];
	}

	return \array_values( \array_unique( $output ) );
}

function filterFilesByExtension( array $files, array $extensions ): array {
	$normalizedExtensions = \array_map( strtolower( ... ), $extensions );

	return \array_values(
		\array_filter(
			$files,
			static function ( string $path ) use ( $normalizedExtensions ): bool {
				$lower = \strtolower( $path );
				foreach ( $normalizedExtensions as $extension ) {
					if ( \str_ends_with( $lower, $extension ) ) {
						return true;
					}
				}

				return false;
			}
		)
	);
}

function ensureComposerIsAvailable(): bool {
	$exitCode = runCommand( 'composer --version', false );
	return $exitCode === 0;
}

function getBinaryPath( string $name ): string {
	$binary = \getcwd().'/vendor/bin/'.$name;
	if ( \PHP_OS_FAMILY === 'Windows' ) {
		$binary = \str_replace( '/', '\\', $binary ).'.bat';
	}
	return \escapeshellarg( $binary );
}

function runQualityTools( array $files ): int {
	if ( $files === [] ) {
		return 0;
	}

	\putenv( 'RECTOR_DISABLE_PARALLEL=1' );

	$escapedFiles = \array_map( escapeshellarg( ... ), $files );
	$filesArg = \implode( ' ', $escapedFiles );

	$rectorBinary = getBinaryPath( 'rector' );
	$rectorExit = runCommand( $rectorBinary.' process '.$filesArg );
	if ( $rectorExit !== 0 ) {
		return $rectorExit;
	}

	// Must specify config when passing multiple paths to php-cs-fixer
	$csFixerBinary = getBinaryPath( 'php-cs-fixer' );
	$csExit = runCommand( $csFixerBinary.' fix --config=.php-cs-fixer.php --allow-risky=yes '.$filesArg );
	if ( $csExit !== 0 ) {
		return $csExit;
	}

	$diffExit = runCommand( 'git diff --quiet -- '.$filesArg, false );
	if ( $diffExit !== 0 ) {
		\fwrite( \STDERR, 'ERROR: Code was reformatted. Please stage the changes and commit again.'.\PHP_EOL );
	}

	return $diffExit;
}

// Main execution
$stagedFiles = gatherStagedFiles();
if ( $stagedFiles === [] ) {
	exit( 0 );
}

$phpFiles = filterFilesByExtension( $stagedFiles, ['.php', '.phtml'] );
if ( $phpFiles === [] ) {
	exit( 0 );
}

if ( !ensureComposerIsAvailable() ) {
	echo 'Pre-commit hook: Composer not available, skipping code quality checks'.\PHP_EOL;
	exit( 0 );
}

echo 'Running code quality tools...'.\PHP_EOL;
$exitCode = runQualityTools( $phpFiles );

if ( $exitCode === 0 ) {
	echo 'Code quality checks passed'.\PHP_EOL;
}

exit( $exitCode );
