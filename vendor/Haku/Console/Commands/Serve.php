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

	#[Override]
	public function options(): array
	{
		return [
			'--port|server port|8000',
			'--host|server host|127.0.0.1',
			'--env|server environment|dev',
		];
	}

	public function invoke(): bool
	{
		try
		{
			$args = (object) $this->arguments->arguments;

			$env = $args?->env ?? 'dev';
			$host = $args?->host ?? '127.0.0.1';
			$port = $args?->port ?? '8000';


			$this->output->output('starting development server...');

			$this->output->success(
				sprintf('server running at: http://%s:%s', $host, $port)
			);

			$this->output->break();

			putenv(sprintf('HAKU_ENVIRONMENT=%s', $env));
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
