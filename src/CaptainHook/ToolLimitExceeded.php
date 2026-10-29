<?php declare( strict_types=1 );

namespace FernleafSystems\QA\CaptainHook;

/** A tool the pre-commit hook ran passed a memory or time limit and was stopped. */
final class ToolLimitExceeded extends \RuntimeException {
}
