<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;
use function Haku\Generic\Strings\random;

/**
 *	@todo Add --size parameter
 */
class Rand extends Command
{

	public function description(): string
	{
		return 'generates a random string';
	}

	public function invoke(): bool
	{
		$this->output->success(random(32));

		return true;
	}

}
