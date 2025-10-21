<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

/**
 *	Runs through available Haku packages and attempts to write documentation for it.
 */
class Okidoki extends Command
{

	public function description(): string
	{
		return 'generates documentation for each package in Haku';
	}

	public function invoke(): bool
	{
		$this->output->info('not implemented :(');

		return true;
	}

}
