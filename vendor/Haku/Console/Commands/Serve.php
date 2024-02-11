<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

class Serve extends Command
{

	public function description(): string
	{
		return 'invokes development server';
	}

	public function invoke(): bool
	{
		try
		{
			$args = (object) $this->arguments->arguments;

			$host = $args?->host ?? '127.0.0.1';
			$port = $args?->port ?? '8000';

			$this->output->output('starting development server...');

			$this->output->success(
				sprintf('server running at: http://%s:%s', $host, $port)
			);

			$this->output->break();

			shell_exec(sprintf('php -S %s:%s', $host, $port));

			return true;
		}
		catch (\Throwable $error)
		{
			$this->output->error($error->getMessage());

			return false;
		}
	}

}
