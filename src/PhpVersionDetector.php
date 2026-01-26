<?php declare( strict_types=1 );

namespace FernleafSystems\QA;

final class PhpVersionDetector {
	private const array SUPPORTED_VERSIONS = ['8.3', '8.4', '8.5'];
	private const string DEFAULT_VERSION = '8.3';

	public static function detect( string $projectPath ): string {
		$composerJsonPath = \rtrim( $projectPath, '/\\' ).'/composer.json';

		if ( !\file_exists( $composerJsonPath ) ) {
			return self::DEFAULT_VERSION;
		}

		$content = \file_get_contents( $composerJsonPath );
		if ( $content === false ) {
			return self::DEFAULT_VERSION;
		}

		$composer = \json_decode( $content, true );
		if ( !\is_array( $composer ) ) {
			return self::DEFAULT_VERSION;
		}

		$phpConstraint = $composer['require']['php'] ?? null;
		if ( $phpConstraint === null ) {
			// Check platform config
			$phpConstraint = $composer['config']['platform']['php'] ?? null;
		}

		if ( $phpConstraint === null ) {
			return self::DEFAULT_VERSION;
		}

		return self::parseConstraint( (string)$phpConstraint );
	}

	private static function parseConstraint( string $constraint ): string {
		$normalized = \preg_replace( '/\s*\|\|\s*/', '||', $constraint );
		if ( $normalized === null ) {
			return self::DEFAULT_VERSION;
		}
		$parts = \explode( '||', $normalized );
		$firstConstraint = \trim( $parts[0] );

		if ( \preg_match( '/(\d+\.\d+)/', $firstConstraint, $matches ) ) {
			$version = $matches[1];

			if ( \in_array( $version, self::SUPPORTED_VERSIONS, true ) ) {
				return $version;
			}

			return self::findClosestVersion( $version );
		}

		return self::DEFAULT_VERSION;
	}

	private static function findClosestVersion( string $version ): string {
		$versionNum = (float)$version;

		foreach ( self::SUPPORTED_VERSIONS as $supported ) {
			if ( (float)$supported >= $versionNum ) {
				return $supported;
			}
		}

		$lastKey = \count( self::SUPPORTED_VERSIONS ) - 1;
		return self::SUPPORTED_VERSIONS[$lastKey];
	}

	/**
	 * Detect if project is a WordPress plugin/theme
	 */
	public static function isWordPressProject( string $projectPath ): bool {
		$composerJsonPath = \rtrim( $projectPath, '/\\' ).'/composer.json';

		if ( !\file_exists( $composerJsonPath ) ) {
			return false;
		}

		$content = \file_get_contents( $composerJsonPath );
		if ( $content === false ) {
			return false;
		}

		$composer = \json_decode( $content, true );
		if ( !\is_array( $composer ) ) {
			return false;
		}

		$type = $composer['type'] ?? '';

		return \in_array( $type, ['wordpress-plugin', 'wordpress-theme', 'wordpress-muplugin'], true );
	}

	/**
	 * @return array<string>
	 */
	public static function getSupportedVersions(): array {
		return self::SUPPORTED_VERSIONS;
	}
}
