<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

use Haku\Spec\Runner;
use Haku\Spec\Reporters\DefaultReporter;

use function Haku\{
	config,
	loadEnvironment,
};

use function Haku\Spec\loadSpecTests;

class Test extends Command
{

	public function description(): string
	{
		return 'runs all, or some (if filtered) spec tests';
	}

	#[Override]
	public function options(): array
	{
		return [
			'--only|runs test matching filter|',
			'--omit|runs all tests except filter|'
		];
	}

	public function invoke(): bool
	{
		try
		{
			$filterOnly = $this->arguments->arguments['only'] ?? '';
			$filterOmit = $this->arguments->arguments['omit'] ?? '';

			loadEnvironment('test');
			loadSpecTests(
				only: $filterOnly,
				omit: $filterOmit,
			);

			$runner = Runner::getInstance();

			if ($runner->numTests() === 0)
			{
				$this->output->info('no tests found!', 'spec');
				$didInvoke = true;

				return false;
			}

			$runner->runAll();

			$reporter = new DefaultReporter(
				$runner,
				$this->output
			);

			$reporter->report();

			$didInvoke = true;
		}
		catch
		(\Throwable $throwable)
		{
			print_r($throwable);

			$didInvoke = false;
		}
		finally
		{
			return $didInvoke;
		}
	}

}
