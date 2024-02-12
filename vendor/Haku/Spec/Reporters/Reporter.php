<?php
declare(strict_types=1);

namespace Haku\Spec\Reporters;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

abstract class Reporter
{

	protected abstract function header(): void;

	protected abstract function footer(): void;

	public abstract function report(): void;

}
