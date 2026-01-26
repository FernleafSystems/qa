<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests;

use FernleafSystems\QA\Installer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Installer::performUninstall()
 *
 * These tests create real files in a temp directory and verify actual removal behavior.
 * No mocking - we test the real file operations.
 */
final class InstallerUninstallTest extends TestCase {
	private string $tempDir;

	protected function setUp(): void {
		$this->tempDir = \sys_get_temp_dir().\DIRECTORY_SEPARATOR.'qa-uninstall-test-'.\uniqid( '', true );
		\mkdir( $this->tempDir, 0755, true );
	}

	protected function tearDown(): void {
		$this->recursiveDelete( $this->tempDir );
	}

	private function recursiveDelete( string $dir ): void {
		if ( !\is_dir( $dir ) ) {
			return;
		}

		$items = \scandir( $dir );
		if ( $items === false ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( $item === '.' || $item === '..' ) {
				continue;
			}

			$path = $dir.\DIRECTORY_SEPARATOR.$item;
			if ( \is_dir( $path ) ) {
				$this->recursiveDelete( $path );
			}
			else {
				\unlink( $path );
			}
		}

		\rmdir( $dir );
	}

	/**
	 * All 8 config files should be removed when they exist.
	 */
	public function testUninstallRemovesAllConfigFiles(): void {
		$configFiles = [
			'captainhook.json',
			'.php-cs-fixer.php',
			'.php-cs-fixer.cache',
			'phpstan.neon',
			'phpstan-baseline.neon',
			'rector.php',
			'.gitattributes',
			'.phpcs.xml.dist',
		];

		// Create all config files
		foreach ( $configFiles as $file ) {
			\file_put_contents( $this->tempDir.\DIRECTORY_SEPARATOR.$file, 'test content' );
		}

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		// Verify all files are gone
		foreach ( $configFiles as $file ) {
			$this->assertFileDoesNotExist(
				$this->tempDir.\DIRECTORY_SEPARATOR.$file,
				"Config file {$file} should have been removed"
			);
		}

		// Verify return value lists all files
		$this->assertCount( 8, $removed );
		foreach ( $configFiles as $file ) {
			$this->assertContains( $file, $removed );
		}
	}

	/**
	 * Uninstall should not fail when some config files don't exist.
	 * Only existing files should be in the return value.
	 */
	public function testUninstallIgnoresMissingConfigFiles(): void {
		// Create only 3 of the 8 config files
		$existingFiles = ['captainhook.json', 'phpstan.neon', 'rector.php'];
		foreach ( $existingFiles as $file ) {
			\file_put_contents( $this->tempDir.\DIRECTORY_SEPARATOR.$file, 'test content' );
		}

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		// Verify only those 3 files are in return value
		$this->assertCount( 3, $removed );
		foreach ( $existingFiles as $file ) {
			$this->assertContains( $file, $removed );
		}

		// Verify non-existent files are not mentioned
		$this->assertNotContains( '.php-cs-fixer.php', $removed );
		$this->assertNotContains( '.gitattributes', $removed );
	}

	/**
	 * Git hooks with CaptainHook signature should be removed.
	 */
	public function testUninstallRemovesCaptainHookGitHooks(): void {
		$hooksDir = $this->tempDir.\DIRECTORY_SEPARATOR.'.git'.\DIRECTORY_SEPARATOR.'hooks';
		\mkdir( $hooksDir, 0755, true );

		// Create a CaptainHook-installed hook (contains 'captainhook' in content)
		$captainHookContent = <<<'HOOK'
#!/bin/sh
# installed by CaptainHook 5.27.4
vendor/bin/captainhook hook:pre-commit "$@"
HOOK;

		\file_put_contents( $hooksDir.\DIRECTORY_SEPARATOR.'pre-commit', $captainHookContent );

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		$this->assertFileDoesNotExist( $hooksDir.\DIRECTORY_SEPARATOR.'pre-commit' );
		$this->assertContains( '.git/hooks/pre-commit', $removed );
	}

	/**
	 * Custom git hooks (not installed by CaptainHook) should be preserved.
	 */
	public function testUninstallPreservesCustomGitHooks(): void {
		$hooksDir = $this->tempDir.\DIRECTORY_SEPARATOR.'.git'.\DIRECTORY_SEPARATOR.'hooks';
		\mkdir( $hooksDir, 0755, true );

		// Create a custom hook (no CaptainHook signature)
		$customHookContent = <<<'HOOK'
#!/bin/sh
# My custom pre-commit hook
echo "Running custom checks..."
./run-my-tests.sh
HOOK;

		\file_put_contents( $hooksDir.\DIRECTORY_SEPARATOR.'pre-commit', $customHookContent );

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		// Custom hook should still exist
		$this->assertFileExists( $hooksDir.\DIRECTORY_SEPARATOR.'pre-commit' );
		$this->assertNotContains( '.git/hooks/pre-commit', $removed );
	}

	/**
	 * Uninstall should handle projects without a .git directory.
	 */
	public function testUninstallHandlesMissingGitDirectory(): void {
		// Create a config file but no .git directory
		\file_put_contents( $this->tempDir.\DIRECTORY_SEPARATOR.'captainhook.json', '{}' );

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		// Should remove config file without error
		$this->assertContains( 'captainhook.json', $removed );

		// Should not contain any hook paths
		foreach ( $removed as $path ) {
			$this->assertStringNotContainsString( '.git/hooks/', $path );
		}
	}

	/**
	 * Uninstall should handle .git directory without hooks subdirectory.
	 */
	public function testUninstallHandlesMissingHooksDirectory(): void {
		// Create .git directory but no hooks subdirectory
		\mkdir( $this->tempDir.\DIRECTORY_SEPARATOR.'.git', 0755 );
		\file_put_contents( $this->tempDir.\DIRECTORY_SEPARATOR.'rector.php', '<?php' );

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		$this->assertContains( 'rector.php', $removed );
		$this->assertCount( 1, $removed );
	}

	/**
	 * Mixed scenario: some config files, some CaptainHook hooks, some custom hooks.
	 * Verifies return value exactly matches what was removed.
	 */
	public function testUninstallReturnValueMatchesActualRemovals(): void {
		$hooksDir = $this->tempDir.\DIRECTORY_SEPARATOR.'.git'.\DIRECTORY_SEPARATOR.'hooks';
		\mkdir( $hooksDir, 0755, true );

		// Create 2 config files
		\file_put_contents( $this->tempDir.\DIRECTORY_SEPARATOR.'phpstan.neon', 'test' );
		\file_put_contents( $this->tempDir.\DIRECTORY_SEPARATOR.'.php-cs-fixer.cache', 'test' );

		// Create 1 CaptainHook hook
		\file_put_contents(
			$hooksDir.\DIRECTORY_SEPARATOR.'pre-commit',
			'#!/bin/sh\nvendor/bin/captainhook hook:pre-commit'
		);

		// Create 1 custom hook (should NOT be removed)
		\file_put_contents(
			$hooksDir.\DIRECTORY_SEPARATOR.'pre-push',
			'#!/bin/sh\necho "custom hook"'
		);

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		// Should have exactly 3 items: 2 config files + 1 CaptainHook hook
		$this->assertCount( 3, $removed );
		$this->assertContains( 'phpstan.neon', $removed );
		$this->assertContains( '.php-cs-fixer.cache', $removed );
		$this->assertContains( '.git/hooks/pre-commit', $removed );

		// Custom hook should NOT be in the list
		$this->assertNotContains( '.git/hooks/pre-push', $removed );

		// Verify actual file state matches
		$this->assertFileDoesNotExist( $this->tempDir.\DIRECTORY_SEPARATOR.'phpstan.neon' );
		$this->assertFileDoesNotExist( $this->tempDir.\DIRECTORY_SEPARATOR.'.php-cs-fixer.cache' );
		$this->assertFileDoesNotExist( $hooksDir.\DIRECTORY_SEPARATOR.'pre-commit' );
		$this->assertFileExists( $hooksDir.\DIRECTORY_SEPARATOR.'pre-push' ); // Custom preserved
	}

	/**
	 * Running uninstall twice should be safe (idempotent).
	 * Second run should return empty array.
	 */
	public function testUninstallIsIdempotent(): void {
		\file_put_contents( $this->tempDir.\DIRECTORY_SEPARATOR.'captainhook.json', '{}' );
		\file_put_contents( $this->tempDir.\DIRECTORY_SEPARATOR.'rector.php', '<?php' );

		$installer = new Installer( $this->tempDir );

		$firstRun = $installer->performUninstall();
		$this->assertCount( 2, $firstRun );

		$secondRun = $installer->performUninstall();
		$this->assertCount( 0, $secondRun );
	}

	/**
	 * All 8 CaptainHook git hooks should be checked for removal.
	 */
	public function testUninstallChecksAllCaptainHookHookTypes(): void {
		$hooksDir = $this->tempDir.\DIRECTORY_SEPARATOR.'.git'.\DIRECTORY_SEPARATOR.'hooks';
		\mkdir( $hooksDir, 0755, true );

		$allHooks = [
			'commit-msg',
			'post-checkout',
			'post-commit',
			'post-merge',
			'post-rewrite',
			'pre-commit',
			'pre-push',
			'prepare-commit-msg',
		];

		// Create all hooks with CaptainHook signature
		foreach ( $allHooks as $hook ) {
			\file_put_contents(
				$hooksDir.\DIRECTORY_SEPARATOR.$hook,
				"#!/bin/sh\n# captainhook installed\nvendor/bin/captainhook hook:{$hook}"
			);
		}

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		// All 8 hooks should be removed
		foreach ( $allHooks as $hook ) {
			$this->assertContains( '.git/hooks/'.$hook, $removed, "Hook {$hook} should be in removed list" );
			$this->assertFileDoesNotExist( $hooksDir.\DIRECTORY_SEPARATOR.$hook, "Hook {$hook} should be deleted" );
		}
	}

	/**
	 * CaptainHook detection should be case-sensitive.
	 * A hook containing 'CAPTAINHOOK' (uppercase) should NOT be detected as CaptainHook.
	 */
	public function testCaptainHookDetectionIsCaseSensitive(): void {
		$hooksDir = $this->tempDir.\DIRECTORY_SEPARATOR.'.git'.\DIRECTORY_SEPARATOR.'hooks';
		\mkdir( $hooksDir, 0755, true );

		// Create hook with uppercase - should NOT be removed
		\file_put_contents(
			$hooksDir.\DIRECTORY_SEPARATOR.'pre-commit',
			"#!/bin/sh\n# CAPTAINHOOK WAS HERE\necho test"
		);

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		// Hook should NOT be removed (case mismatch)
		$this->assertFileExists( $hooksDir.\DIRECTORY_SEPARATOR.'pre-commit' );
		$this->assertNotContains( '.git/hooks/pre-commit', $removed );
	}

	/**
	 * Empty hook files should not cause errors.
	 */
	public function testUninstallHandlesEmptyHookFiles(): void {
		$hooksDir = $this->tempDir.\DIRECTORY_SEPARATOR.'.git'.\DIRECTORY_SEPARATOR.'hooks';
		\mkdir( $hooksDir, 0755, true );

		// Create empty hook file
		\file_put_contents( $hooksDir.\DIRECTORY_SEPARATOR.'pre-commit', '' );

		$installer = new Installer( $this->tempDir );
		$removed = $installer->performUninstall();

		// Empty file should NOT be removed (no captainhook signature)
		$this->assertFileExists( $hooksDir.\DIRECTORY_SEPARATOR.'pre-commit' );
		$this->assertNotContains( '.git/hooks/pre-commit', $removed );
	}
}
