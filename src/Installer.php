<?php declare( strict_types=1 );

namespace FernleafSystems\QA;

use Composer\Script\Event;

final class Installer {
	private const RESOURCES_DIR = __DIR__.'/../resources';

	private string $projectPath;

	public function __construct( string $projectPath = '' ) {
		$this->projectPath = $projectPath ?: \getcwd();
	}

	/**
	 * Display post-install message.
	 */
	public static function postInstallMessage( Event $event ): void {
		$io = $event->getIO();
		$io->write( '' );
		$io->write( '<info>╔════════════════════════════════════════════════════════════╗</info>' );
		$io->write( '<info>║</info>  FernleafSystems QA installed!                            <info>║</info>' );
		$io->write( '<info>║</info>  Run <comment>vendor/bin/qa-setup</comment> to generate config files.      <info>║</info>' );
		$io->write( '<info>╚════════════════════════════════════════════════════════════╝</info>' );
		$io->write( '' );
	}

	/**
	 * Composer script entry point.
	 */
	public static function setup( Event $event ): void {
		$io = $event->getIO();
		$projectPath = \getcwd();

		$io->write( '<info>FernleafSystems QA Setup</info>' );
		$io->write( '' );

		// Detect PHP version
		$detectedVersion = PhpVersionDetector::detect( $projectPath );
		$isWordPress = PhpVersionDetector::isWordPressProject( $projectPath );

		$io->write( sprintf( 'Detected PHP version: <comment>%s</comment>', $detectedVersion ) );
		if ( $isWordPress ) {
			$io->write( '<comment>WordPress project detected</comment>' );
		}

		// Ask for confirmation
		$phpVersion = $io->ask(
			sprintf( 'Use PHP version [<comment>%s</comment>]: ', $detectedVersion ),
			$detectedVersion
		);

		if ( !\in_array( $phpVersion, PhpVersionDetector::getSupportedVersions(), true ) ) {
			$io->writeError( sprintf( '<error>Unsupported PHP version: %s</error>', $phpVersion ) );
			return;
		}

		// Detect source paths
		$detectedSrcPath = self::detectSourcePath( $projectPath );
		$srcPath = $io->ask(
			sprintf( 'Source directory [<comment>%s</comment>]: ', $detectedSrcPath ),
			$detectedSrcPath
		);

		// Detect tests path
		$hasTests = \is_dir( $projectPath.'/tests' );
		$testsPath = $hasTests ? 'tests' : '';
		if ( $hasTests ) {
			$includeTests = $io->askConfirmation( 'Include tests directory? [<comment>Y/n</comment>]: ', true );
			$testsPath = $includeTests ? 'tests' : '';
		}

		// Ask about PHPStan level
		$phpstanLevel = $io->ask(
			'PHPStan level (3=conservative, 6=strict) [<comment>3</comment>]: ',
			'3'
		);

		// Generate configurations
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
		$io->write( '  3. Run <comment>composer cs-check</comment> to check code style' );
		$io->write( '  4. Run <comment>composer phpstan</comment> to run static analysis' );
	}

	/**
	 * Standalone CLI entry point.
	 */
	public function run(): void {
		echo "FernleafSystems QA Setup\n";
		echo "========================\n\n";

		// Detect PHP version
		$detectedVersion = PhpVersionDetector::detect( $this->projectPath );
		$isWordPress = PhpVersionDetector::isWordPressProject( $this->projectPath );

		echo "Detected PHP version: {$detectedVersion}\n";
		if ( $isWordPress ) {
			echo "WordPress project detected\n";
		}

		// Ask for PHP version
		$phpVersion = $this->prompt( "Use PHP version [{$detectedVersion}]: ", $detectedVersion );

		if ( !\in_array( $phpVersion, PhpVersionDetector::getSupportedVersions(), true ) ) {
			\fwrite( \STDERR, "Error: Unsupported PHP version: {$phpVersion}\n" );
			exit( 1 );
		}

		// Detect source paths
		$detectedSrcPath = self::detectSourcePath( $this->projectPath );
		$srcPath = $this->prompt( "Source directory [{$detectedSrcPath}]: ", $detectedSrcPath );

		// Detect tests path
		$hasTests = \is_dir( $this->projectPath.'/tests' );
		$testsPath = '';
		if ( $hasTests ) {
			$includeTests = $this->promptYesNo( 'Include tests directory? [Y/n]: ', true );
			$testsPath = $includeTests ? 'tests' : '';
		}

		// Ask about PHPStan level
		$phpstanLevel = $this->prompt( 'PHPStan level (3=conservative, 6=strict) [3]: ', '3' );

		// Generate configurations
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
		echo "  3. Run 'composer cs-check' to check code style\n";
		echo "  4. Run 'composer phpstan' to run static analysis\n";
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

use FernleafSystems\QA\PhpCsFixer\ConfigFactory;
use {$ruleSetClass};
use PhpCsFixer\Finder;

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
	 * @param array<string, string> $paths
	 */
	public function generateRectorConfig( string $phpVersion, array $paths = [] ): void {
		$isPhp8Plus = \version_compare( $phpVersion, '8.0', '>=' );

		if ( $isPhp8Plus ) {
			$this->generateRectorConfigPhp8( $phpVersion, $paths );
		}
		else {
			$this->generateRectorConfigPhp74( $paths );
		}
	}

	/**
	 * @param array<string, string> $paths
	 */
	private function generateRectorConfigPhp74( array $paths = [] ): void {
		$srcPath = !empty( $paths['src'] ) ? $paths['src'] : 'src';
		$pathLines = "\t\t__DIR__.'/{$srcPath}',";
		if ( !empty( $paths['tests'] ) ) {
			$pathLines .= "\n\t\t__DIR__.'/tests',";
		}

		$content = <<<PHP
<?php declare( strict_types=1 );

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

\$config = RectorConfig::configure()
	->withPaths( [
{$pathLines}
	] )
	->withSkip( [
		__DIR__.'/vendor',
		StringClassNameToClassConstantRector::class,
	] )
	->withSets( [
		LevelSetList::UP_TO_PHP_74,
	] )
	->withRules( [
		ShortenElseIfRector::class,
		AddVoidReturnTypeWhereNoReturnRector::class,
		TypedPropertyFromStrictConstructorRector::class,
		RemoveUnusedPrivateMethodRector::class,
		RemoveUnusedPrivatePropertyRector::class,
		RemoveUnusedPrivateMethodParameterRector::class,
		RemoveUnusedVariableAssignRector::class,
		SimplifyUselessVariableRector::class,
	] )
	->withImportNames(
		importShortClasses: false,
		removeUnusedImports: true
	);

if ( (bool) getenv( 'RECTOR_DISABLE_PARALLEL' ) ) {
	return \$config->withoutParallel();
}

return \$config->withParallel( 360, 4 );

PHP;

		$this->writeFile( $this->projectPath.'/rector.php', $content );
	}

	/**
	 * @param array<string, string> $paths
	 */
	private function generateRectorConfigPhp8( string $phpVersion, array $paths = [] ): void {
		$phpSetsArg = 'php'.\str_replace( '.', '', $phpVersion );
		$srcPath = !empty( $paths['src'] ) ? $paths['src'] : 'src';
		$pathLines = "\t\t__DIR__.'/{$srcPath}',";
		if ( !empty( $paths['tests'] ) ) {
			$pathLines .= "\n\t\t__DIR__.'/tests',";
		}

		$content = <<<PHP
<?php declare( strict_types=1 );

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

\$config = RectorConfig::configure()
	->withPaths( [
{$pathLines}
	] )
	->withSkip( [
		__DIR__.'/vendor',
		StringClassNameToClassConstantRector::class,
	] )
	->withPhpSets(
		{$phpSetsArg}: true
	)
	->withRules( [
		ClassPropertyAssignToConstructorPromotionRector::class,
		ShortenElseIfRector::class,
		AddVoidReturnTypeWhereNoReturnRector::class,
		TypedPropertyFromStrictConstructorRector::class,
		RemoveUnusedPrivateMethodRector::class,
		RemoveUnusedPrivatePropertyRector::class,
		RemoveUnusedPrivateMethodParameterRector::class,
		RemoveUnusedVariableAssignRector::class,
		SimplifyUselessVariableRector::class,
		ChangeSwitchToMatchRector::class,
	] )
	->withImportNames(
		importShortClasses: false,
		removeUnusedImports: true
	);

if ( filter_var( getenv( 'RECTOR_DISABLE_PARALLEL' ), FILTER_VALIDATE_BOOL ) ) {
	return \$config->withoutParallel();
}

return \$config->withParallel(
	timeoutSeconds: 360,
	maxNumberOfProcess: 4
);

PHP;

		$this->writeFile( $this->projectPath.'/rector.php', $content );
	}

	/**
	 * @param array<string, string> $paths
	 */
	public function generatePHPStanConfig( string $phpVersion, int $level = 6, array $paths = [] ): void {
		$versionMap = [
			'7.4' => 70400,
			'8.0' => 80000,
			'8.1' => 80100,
			'8.2' => 80200,
			'8.3' => 80300,
			'8.4' => 80400,
			'8.5' => 80500,
		];
		$phpVersionInt = $versionMap[$phpVersion] ?? 80300;

		$srcPath = !empty( $paths['src'] ) ? $paths['src'] : 'src';
		$pathLines = "        - {$srcPath}";
		if ( !empty( $paths['tests'] ) ) {
			$pathLines .= "\n        - tests";
		}

		$content = <<<NEON
parameters:
    phpVersion: {$phpVersionInt}
    level: {$level}

    paths:
{$pathLines}

    excludePaths:
        - */vendor/*

    bootstrapFiles:
        - vendor/autoload.php

    # FernleafSystems standard settings
    treatPhpDocTypesAsCertain: false
    reportUnmatchedIgnoredErrors: false

NEON;

		$this->writeFile( $this->projectPath.'/phpstan.neon', $content );
	}

	public function generateCaptainHookConfig(): void {
		$sourceFile = self::RESOURCES_DIR.'/captainhook/captainhook.json';
		$content = \file_get_contents( $sourceFile );

		$this->writeFile( $this->projectPath.'/captainhook.json', $content );
	}

	public function generateGitAttributes(): void {
		$sourceFile = self::RESOURCES_DIR.'/gitattributes';
		$content = \file_get_contents( $sourceFile );

		$this->writeFile( $this->projectPath.'/.gitattributes', $content );
	}

	/**
	 * @param array<string, string> $paths
	 */
	public function generatePhpcsConfig( string $phpVersion, array $paths = [] ): void {
		$sourceFile = self::RESOURCES_DIR.'/phpcs/wordpress.xml.dist';
		$content = \file_get_contents( $sourceFile );

		// Update PHP version
		$content = \preg_replace(
			'/value="[\d.]+-"/',
			'value="'.$phpVersion.'-"',
			$content
		);

		// Update source path
		$srcPath = !empty( $paths['src'] ) ? $paths['src'] : 'src';
		$content = \preg_replace(
			'/<file>src<\/file>/',
			'<file>'.$srcPath.'</file>',
			$content
		);

		$this->writeFile( $this->projectPath.'/.phpcs.xml.dist', $content );
	}

	private function getPhpCsFixerRuleSetClass( string $phpVersion ): string {
		$map = [
			'7.4' => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php74',
			'8.0' => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php83',
			'8.1' => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php83',
			'8.2' => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php83',
			'8.3' => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php83',
			'8.4' => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php84',
			'8.5' => 'FernleafSystems\\QA\\PhpCsFixer\\RuleSet\\Php85',
		];

		return $map[$phpVersion] ?? $map['8.3'];
	}

	private function writeFile( string $path, string $content ): void {
		$dir = \dirname( $path );
		if ( !\is_dir( $dir ) ) {
			\mkdir( $dir, 0755, true );
		}

		\file_put_contents( $path, $content );
	}
}
