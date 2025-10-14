<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

use function Haku\manifest;

class Version extends Command
{

	public function description(): string
	{
		return 'displays the current haku version';
	}

	public function invoke(): bool
	{
		$pkg = manifest();
		$currentVersion = $pkg->version ?? '0.0.0';

		$this->output->output($currentVersion);

		return true;
	}

}
