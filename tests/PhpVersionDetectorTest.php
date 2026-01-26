<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests;

use FernleafSystems\QA\PhpVersionDetector;
use PHPUnit\Framework\TestCase;

final class PhpVersionDetectorTest extends TestCase {
	/**
	 * @var string
	 */
	private $tempDir;

	protected function setUp(): void {
		$this->tempDir = \sys_get_temp_dir().'/qa-test-'.\uniqid( '', true );
		\mkdir( $this->tempDir );
	}

	protected function tearDown(): void {
		if ( \is_dir( $this->tempDir ) ) {
			$files = \glob( $this->tempDir.'/*' );
			if ( $files !== false ) {
				\array_map( 'unlink', $files );
			}
			\rmdir( $this->tempDir );
		}
	}

	public function testDetectReturnsDefaultWhenNoComposerJson(): void {
		$version = PhpVersionDetector::detect( $this->tempDir );
		$this->assertSame( '8.3', $version );
	}

	public function testDetectParsesCaret74(): void {
		$this->createComposerJson( ['require' => ['php' => '^7.4']] );
		$version = PhpVersionDetector::detect( $this->tempDir );
		$this->assertSame( '7.4', $version );
	}

	public function testDetectParsesCaret83(): void {
		$this->createComposerJson( ['require' => ['php' => '^8.3']] );
		$version = PhpVersionDetector::detect( $this->tempDir );
		$this->assertSame( '8.3', $version );
	}

	public function testDetectParsesOrConstraint(): void {
		$this->createComposerJson( ['require' => ['php' => '^7.4 || ^8.0']] );
		$version = PhpVersionDetector::detect( $this->tempDir );
		$this->assertSame( '7.4', $version );
	}

	public function testDetectParsesPlatformConfig(): void {
		$this->createComposerJson( [
			'config' => ['platform' => ['php' => '7.4']],
		] );
		$version = PhpVersionDetector::detect( $this->tempDir );
		$this->assertSame( '7.4', $version );
	}

	public function testIsWordPressProjectReturnsTrueForPlugin(): void {
		$this->createComposerJson( ['type' => 'wordpress-plugin'] );
		$this->assertTrue( PhpVersionDetector::isWordPressProject( $this->tempDir ) );
	}

	public function testIsWordPressProjectReturnsFalseForLibrary(): void {
		$this->createComposerJson( ['type' => 'library'] );
		$this->assertFalse( PhpVersionDetector::isWordPressProject( $this->tempDir ) );
	}

	public function testGetSupportedVersions(): void {
		$versions = PhpVersionDetector::getSupportedVersions();
		$this->assertContains( '7.4', $versions );
		$this->assertContains( '8.3', $versions );
		$this->assertContains( '8.4', $versions );
	}

	/**
	 * @param array<string, mixed> $content
	 */
	private function createComposerJson( array $content ): void {
		\file_put_contents(
			$this->tempDir.'/composer.json',
			\json_encode( $content, \JSON_PRETTY_PRINT )
		);
	}
}
