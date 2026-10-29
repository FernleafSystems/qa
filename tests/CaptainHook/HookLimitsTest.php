<?php declare( strict_types=1 );

namespace FernleafSystems\QA\Tests\CaptainHook;

use FernleafSystems\QA\CaptainHook\HookLimits;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HookLimitsTest extends TestCase {
	public function testDefaultsOnlyCatchARunaway(): void {
		$limits = HookLimits::from( [], [] );
		$this->assertSame( [ 8192, 1800, false ], [ $limits->maxMemoryMb, $limits->timeoutSeconds, $limits->allowExternalTools ] );
	}

	public function testTheRepositorySetsThemAndTheEnvironmentOverridesIt(): void {
		$options = [ 'max_memory_mb' => 16384, 'timeout_seconds' => '3600', 'allow_external_tools' => true ];
		$fromOptions = HookLimits::from( $options, [] );
		$this->assertSame( [ 16384, 3600, true ], [ $fromOptions->maxMemoryMb, $fromOptions->timeoutSeconds, $fromOptions->allowExternalTools ] );

		$overridden = HookLimits::from( $options, [
			HookLimits::ENV_MAX_MEMORY_MB        => '0',
			HookLimits::ENV_TIMEOUT_SECONDS      => '60',
			HookLimits::ENV_ALLOW_EXTERNAL_TOOLS => '0',
		] );
		$this->assertSame( [ 0, 60, false ], [ $overridden->maxMemoryMb, $overridden->timeoutSeconds, $overridden->allowExternalTools ] );

		$unset = HookLimits::from( $options, [ HookLimits::ENV_MAX_MEMORY_MB => false, HookLimits::ENV_TIMEOUT_SECONDS => '' ] );
		$this->assertSame( [ 16384, 3600 ], [ $unset->maxMemoryMb, $unset->timeoutSeconds ], 'An unset or empty variable leaves the option' );
	}

	#[DataProvider( 'invalid' )]
	public function testRejectsAnInvalidLimit( array $options, array $environment ): void {
		$this->expectException( \InvalidArgumentException::class );
		HookLimits::from( $options, $environment );
	}

	public static function invalid(): iterable {
		yield 'negative option' => [ [ 'max_memory_mb' => -1 ], [] ];
		yield 'non-numeric option' => [ [ 'timeout_seconds' => 'soon' ], [] ];
		yield 'non-numeric variable' => [ [], [ HookLimits::ENV_MAX_MEMORY_MB => '8G' ] ];
		yield 'boolean limit' => [ [ 'timeout_seconds' => true ], [] ];
		yield 'unrecognised flag' => [ [ 'allow_external_tools' => 'maybe' ], [] ];
	}
}
