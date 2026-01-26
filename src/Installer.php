<?php declare( strict_types=1 );

namespace FernleafSystems\QA;

use Composer\Script\Event;

final readonly class Installer {
	private const string RESOURCES_DIR = __DIR__.'/../resources';

	private string $projectPath;

	public function __construct( string $projectPath = '' ) {
		$this->projectPath = $projectPath ?: \getcwd();
	}

	/**
	 * Composer script entry point.
	 */
	public static function setup( Event $event ): void {
		$io = $event->getIO();
		$projectPath = \getcwd();

		$io->write( '<info>FernleafSystems QA Setup</info>' );
		$io->write( '' );

		$detectedVersion = PhpVersionDetector::detect( $projectPath );
		$isWordPress = PhpVersionDetector::isWordPressProject( $projectPath );

		$io->write( sprintf( 'Detected PHP version: <comment>%s</comment>', $detectedVersion ) );
		if ( $isWordPress ) {
			$io->write( '<comment>WordPress project detected</comment>' );
		}

		$phpVersion = $io->ask(
			sprintf( 'Use PHP version [<comment>%s</comment>]: ', $detectedVersion ),
			$detectedVersion
		);

		if ( !\in_array( $phpVersion, PhpVersionDetector::getSupportedVersions(), true ) ) {
			$io->writeError( sprintf( '<error>Unsupported PHP version: %s</error>', $phpVersion ) );
			return;
		}

		$detectedSrcPath = self::detectSourcePath( $projectPath );
		$srcPath = $io->ask(
			sprintf( 'Source directory [<comment>%s</comment>]: ', $detectedSrcPath ),
			$detectedSrcPath
		);

		$hasTests = \is_dir( $projectPath.'/tests' );
		$testsPath = '';
		if ( $hasTests ) {
			$includeTests = $io->askConfirmation( 'Include tests directory? [<comment>Y/n</comment>]: ', true );
			$testsPath = $includeTests ? 'tests' : '';
		}

		$phpstanLevel = $io->ask(
			'PHPStan level (3=conservative, 6=strict) [<comment>3</comment>]: ',
			'3'
		);

		$installer = new self( $projectPath );
		$paths = ['src' => $srcPath, 'tests' => $testsPath];

		$io->write( '' );
		$io->write( 'Generating configuration files...' );

		if ( $isWordPress ) {
			$installer->generatePhpcsConfig( $phpVersion, $paths );
			$io->write( '  <info>Created</info> .phpcs.xml.dist (WordPress Coding Standards)' );
		}
		else {
			$installer->generatePhpCsFixerConfig( $phpVersion, $paths );
			$io->write( '  <info>Created</info> .php-cs-fixer.php' );
		}

		$installer->generateRectorConfig( $phpVersion, $paths );
		$io->write( '  <info>Created</info> rector.php' );

		$installer->generatePHPStanConfig( $phpVersion, (int)$phpstanLevel, $paths );
		$io->write( '  <info>Created</info> phpstan.neon' );

		$installer->generateCaptainHookConfig();
		$io->write( '  <info>Created</info> captainhook.json' );

		$installer->generateGitAttributes();
		$io->write( '  <info>Created</info> .gitattributes' );

		$io->write( '' );
		$io->write( '<info>Setup complete!</info>' );
		$io->write( '' );
		$io->write( 'Next steps:' );
		$io->write( '  1. Review the generated configuration files' );
		$io->write( '  2. Run <comment>composer install</comment> to install git hooks' );
		$io->write( '  3. Run <comment>vendor/bin/php-cs-fixer fix</comment> to fix code style' );
		$io->write( '  4. Run <comment>vendor/bin/rector process</comment> to run refactoring' );
		$io->write( '  5. Run <comment>vendor/bin/phpstan analyse</comment> to run static analysis' );
	}

	/**
	 * Standalone CLI entry point.
	 */
	public function run(): void {
		echo "FernleafSystems QA Setup\n";
		echo "========================\n\n";

		$detectedVersion = PhpVersionDetector::detect( $this->projectPath );
		$isWordPress = PhpVersionDetector::isWordPressProject( $this->projectPath );

		echo "Detected PHP version: {$detectedVersion}\n";
		if ( $isWordPress ) {
			echo "WordPress project detected\n";
		}

		$phpVersion = $this->prompt( "Use PHP version [{$detectedVersion}]: ", $detectedVersion );

		if ( !\in_array( $phpVersion, PhpVersionDetector::getSupportedVersions(), true ) ) {
			\fwrite( \STDERR, "Error: Unsupported PHP version: {$phpVersion}\n" );
			exit( 1 );
		}

		$detectedSrcPath = self::detectSourcePath( $this->projectPath );
		$srcPath = $this->prompt( "Source directory [{$detectedSrcPath}]: ", $detectedSrcPath );

		$hasTests = \is_dir( $this->projectPath.'/tests' );
		$testsPath = '';
		if ( $hasTests ) {
			$includeTests = $this->promptYesNo( 'Include tests directory? [Y/n]: ', true );
			$testsPath = $includeTests ? 'tests' : '';
		}

		$phpstanLevel = $this->prompt( 'PHPStan level (3=conservative, 6=strict) [3]: ', '3' );

		$paths = ['src' => $srcPath, 'tests' => $testsPath];

		echo "\nGenerating configuration files...\n";

		if ( $isWordPress ) {
			$this->generatePhpcsConfig( $phpVersion, $paths );
			echo "  Created .phpcs.xml.dist (WordPress Coding Standards)\n";
		}
		else {
			$this->generatePhpCsFixerConfig( $phpVersion, $paths );
			echo "  Created .php-cs-fixer.php\n";
		}

		$this->generateRectorConfig( $phpVersion, $paths );
		echo "  Created rector.php\n";

		$this->generatePHPStanConfig( $phpVersion, (int)$phpstanLevel, $paths );
		echo "  Created phpstan.neon\n";

		$this->generateCaptainHookConfig();
		echo "  Created captainhook.json\n";

		$this->generateGitAttributes();
		echo "  Created .gitattributes\n";

		echo "\nSetup complete!\n\n";
		echo "Next steps:\n";
		echo "  1. Review the generated configuration files\n";
		echo "  2. Run 'composer install' to install git hooks\n";
		echo "  3. Run 'vendor/bin/php-cs-fixer fix' to fix code style\n";
		echo "  4. Run 'vendor/bin/rector process' to run refactoring\n";
		echo "  5. Run 'vendor/bin/phpstan analyse' to run static analysis\n";
	}

	private function prompt( string $question, string $default = '' ): string {
		echo $question;
		$input = \trim( \fgets( \STDIN ) ?: '' );
		return $input !== '' ? $input : $default;
	}

	private function promptYesNo( string $question, bool $default = true ): bool {
		echo $question;
		$input = \strtolower( \trim( \fgets( \STDIN ) ?: '' ) );
		if ( $input === '' ) {
			return $default;
		}
		return \in_array( $input, ['y', 'yes', '1', 'true'], true );
	}

	private static function detectSourcePath( string $projectPath ): string {
		$candidates = ['src', 'lib', 'app', 'source', 'classes'];
		foreach ( $candidates as $candidate ) {
			if ( \is_dir( $projectPath.'/'.$candidate ) ) {
				return $candidate;
			}
		}
		return 'src';
	}

	/**
	 * Generate PHP CS Fixer config that links to QA library rulesets.
	 *
	 * @param array<string, string> $paths
	 */
	public function generatePhpCsFixerConfig( string $phpVersion, array $paths = [] ): void {
		$ruleSetClass = $this->getPhpCsFixerRuleSetClass( $phpVersion );
		$shortClass = \basename( \str_replace( '\\', '/', $ruleSetClass ) );

		$srcPath = !empty( $paths['src'] ) ? $paths['src'] : 'src';
		$inPaths = ["__DIR__.'/{$srcPath}'"];
		if ( !empty( $paths['tests'] ) ) {
			$inPaths[] = "__DIR__.'/tests'";
		}
		$inPathsStr = \implode( " )\n\t->in( ", $inPaths );

		$content = <<<PHP
<?php declare( strict_types=1 );

/**
 * PHP CS Fixer configuration.
 * Rules are loaded from fernleafsystems/qa - update that package to get new rules.
 */

use FernleafSystems\\QA\\PhpCsFixer\\ConfigFactory;
use {$ruleSetClass};
use PhpCsFixer\\Finder;

\$finder = Finder::create()
	->in( {$inPathsStr} )
	->exclude( 'vendor' );

return ConfigFactory::fromRuleSet( new {$shortClass}() )
	->withFinder( \$finder )
	->create();

PHP;

		$this->writeFile( $this->projectPath.'/.php-cs-fixer.php', $content );
	}

	/**
	 * Generate Rector config that links to QA library rulesets.
	 *
	 * @param array<string, string> $paths
	 */
	public function generateRectorConfig( string $phpVersion, array $paths = [] ): void {
		$srcPath = !empty( $paths['src'] ) ? $paths['src'] : 'src';
		$pathLines = "\t__DIR__.'/{$srcPath}',";
		if ( !empty( $paths['tests'] ) ) {
			$pathLines .= "\n\t__DIR__.'/tests',";
		}

		$content = <<<PHP
<?php declare( strict_types=1 );

/**
 * Rector configuration.
 * Rules are loaded from fernleafsystems/qa - update that package to get new rules.
 */

use FernleafSystems\\QA\\Rector\\ConfigFactory;

return ConfigFactory::forPhpVersion( '{$phpVersion}' )
	->withPaths( [
{$pathLines}
	] )
	->withSkip( [
		__DIR__.'/vendor',
	] )
	->create();

PHP;

		$this->writeFile( $this->projectPath.'/rector.php', $content );
	}

	/**
	 * Generate PHPStan config that includes QA library base rules.
	 *
	 * @param array<string, string> $paths
	 */
	public function generatePHPStanConfig( string $phpVersion, int $level = 3, array $paths = [] ): void {
		$phpVersionInt = match ( $phpVersion ) {
			'8.4'   => 80400,
			'8.5'   => 80500,
			default => 80300,
		};

		$srcPath = !empty( $paths['src'] ) ? $paths['src'] : 'src';
		$pathLines = "        - {$srcPath}";
		if ( !empty( $paths['tests'] ) ) {
			$pathLines .= "\n        - tests";
		}

		$content = <<<NEON
#
# PHPStan configuration.
# Base rules are loaded from fernleafsystems/qa - update that package to get new rules.
#
includes:
    - vendor/fernleafsystems/qa/resources/phpstan/rules.neon

parameters:
    phpVersion: {$phpVersionInt}
    level: {$level}

    paths:
{$pathLines}

    excludePaths:
        - */vendor/*

NEON;

		$this->writeFile( $this->projectPath.'/phpstan.neon', $content );
	}

	public function generateCaptainHookConfig(): void {
		$content = \file_get_contents( self::RESOURCES_DIR.'/captainhook/captainhook.json' );
		$this->writeFile( $this->projectPath.'/captainhook.json', $content );
	}

	public function generateGitAttributes(): void {
		$content = \file_get_contents( self::RESOURCES_DIR.'/gitattributes' );
		$this->writeFile( $this->projectPath.'/.gitattributes', $content );
	}

	/**
	 * @param array<string, string> $paths
	 */
	public function generatePhpcsConfig( string $phpVersion, array $paths = [] ): void {
		$content = \file_get_contents( self::RESOURCES_DIR.'/phpcs/wordpress.xml.dist' );

		$content = \preg_replace(
			'/value="[\d.]+-"/',
			'value="'.$phpVersion.'-"',
			$content
		);

		$srcPath = !empty( $paths['src'] ) ? $paths['src'] : 'src';
		$content = \preg_replace(
			'/<file>src<\/file>/',
			'<file>'.$srcPath.'</file>',
			(string) $content
		);

		$this->writeFile( $this->projectPath.'/.phpcs.xml.dist', $content );
	}

	private function getPhpCsFixerRuleSetClass( string $phpVersion ): string {
		return match ( $phpVersion ) {
			'8.4'   => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php84',
			'8.5'   => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php85',
			default => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php83',
		};
	}

	private function writeFile( string $path, string $content ): void {
		$dir = \dirname( $path );
		if ( !\is_dir( $dir ) ) {
			\mkdir( $dir, 0755, true );
		}

		\file_put_contents( $path, $content );
	}
}
