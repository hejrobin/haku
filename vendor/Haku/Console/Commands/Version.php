<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

use function Haku\package;

class Version extends Command
{

	public function description(): string
	{
		return 'displays current haku version';
	}

	public function invoke(): bool
	{
		$pkg = package();

		$this->output->output($pkg->version);

		return true;
	}

}
