<?php declare( strict_types=1 );

namespace FernleafSystems\QA;

use Composer\Script\Event;

final class Installer {
	private const RESOURCES_DIR = __DIR__.'/../resources';

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
		$installer = new self();
		$paths = ['src' => $srcPath, 'tests' => $testsPath];

		$io->write( '' );
		$io->write( 'Generating configuration files...' );

		if ( $isWordPress ) {
			$installer->generatePhpcsConfig( $projectPath, $phpVersion, $paths );
			$io->write( '  <info>Created</info> .phpcs.xml.dist (WordPress Coding Standards)' );
		}
		else {
			$installer->generatePhpCsFixerConfig( $projectPath, $phpVersion, $paths );
			$io->write( '  <info>Created</info> .php-cs-fixer.php' );
		}

		$installer->generateRectorConfig( $projectPath, $phpVersion, $paths );
		$io->write( '  <info>Created</info> rector.php' );

		$installer->generatePHPStanConfig( $projectPath, $phpVersion, (int)$phpstanLevel, $paths );
		$io->write( '  <info>Created</info> phpstan.neon' );

		$installer->generateCaptainHookConfig( $projectPath );
		$io->write( '  <info>Created</info> captainhook.json' );

		$installer->generateGitAttributes( $projectPath );
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
	public function generatePhpCsFixerConfig( string $projectPath, string $phpVersion, array $paths = [] ): void {
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

		$this->writeFile( $projectPath.'/.php-cs-fixer.php', $content );
	}

	/**
	 * @param array<string, string> $paths
	 */
	public function generateRectorConfig( string $projectPath, string $phpVersion, array $paths = [] ): void {
		$isPhp8Plus = \version_compare( $phpVersion, '8.0', '>=' );

		if ( $isPhp8Plus ) {
			$this->generateRectorConfigPhp8( $projectPath, $phpVersion, $paths );
		}
		else {
			$this->generateRectorConfigPhp74( $projectPath, $paths );
		}
	}

	/**
	 * @param array<string, string> $paths
	 */
	private function generateRectorConfigPhp74( string $projectPath, array $paths = [] ): void {
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

		$this->writeFile( $projectPath.'/rector.php', $content );
	}

	/**
	 * @param array<string, string> $paths
	 */
	private function generateRectorConfigPhp8( string $projectPath, string $phpVersion, array $paths = [] ): void {
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

		$this->writeFile( $projectPath.'/rector.php', $content );
	}

	/**
	 * @param array<string, string> $paths
	 */
	public function generatePHPStanConfig( string $projectPath, string $phpVersion, int $level = 6, array $paths = [] ): void {
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

		$this->writeFile( $projectPath.'/phpstan.neon', $content );
	}

	public function generateCaptainHookConfig( string $projectPath ): void {
		$sourceFile = self::RESOURCES_DIR.'/captainhook/captainhook.json';
		$content = \file_get_contents( $sourceFile );

		$this->writeFile( $projectPath.'/captainhook.json', $content );
	}

	public function generateGitAttributes( string $projectPath ): void {
		$sourceFile = self::RESOURCES_DIR.'/gitattributes';
		$content = \file_get_contents( $sourceFile );

		$this->writeFile( $projectPath.'/.gitattributes', $content );
	}

	/**
	 * @param array<string, string> $paths
	 */
	public function generatePhpcsConfig( string $projectPath, string $phpVersion, array $paths = [] ): void {
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

		$this->writeFile( $projectPath.'/.phpcs.xml.dist', $content );
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
