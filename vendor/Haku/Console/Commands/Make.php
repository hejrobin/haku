<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Override;

use Haku\Console\Command;

use Haku\Console\Ansi;

use Haku\Console\Commands\Generators\{
	Generator,
	AvailableGenerators
};

use function Haku\Console\{
	resolveArguments,
	calculateIndentLength
};

use function Haku\Console\Commands\Generators\getGeneratorInstance;

/**
 * Code generation command "make", see {@see vendor/Haku/Console/Commands/Generators}.
 */
class Make extends Command
{

	#[Override]
	protected function resolveArguments(): void
	{
		$this->arguments = (object) resolveArguments();
	}

	#[Override]
	public function options(): array
	{
		return [];
	}

	public function description(): string
	{
		return 'code generator tools';
	}

	#[Override]
	public function requiresContext(): bool
	{
		return true;
	}

	public function help(): void
	{
		$cli = $this->output;

		$cli->output(sprintf(
			"usage: haku %s <generator>",
			$cli->format('make', Ansi::Yellow)
		));

		$cli->break();
		$cli->send('available generators:');
		$cli->break();

		$rows = [];

		$generators = AvailableGenerators::help();

		foreach ($generators as $key => $item)
		{
			[, $params] = array_map('mb_trim', explode('|', "$item|"));

			$rows[] = $params;
		}

		$indentLength = calculateIndentLength(array_keys($generators)) + 2;
		$helpIndentLength = calculateIndentLength($rows) + 2;

		foreach ($generators as $generator => $help)
		{
			[$description, $params] = array_map('mb_trim', explode('|', "$help|"));

			$helpIndent = $helpIndentLength - strlen($params);

			if ($params === '')
			{
				$params = '';
				$helpIndent -= strlen($params);
			}

			$description = str_pad('', $helpIndent, ' ', STR_PAD_LEFT) . $description;

			$cli->send(sprintf(
				'%s %s %s',
				$cli->format(str_pad($generator, $indentLength), Ansi::Yellow),
				$params,
				$description
			));
		}
	}

	public function invoke(): bool
	{
		if (!isset($this->arguments->context))
		{
			$this->output->info('make requires a generator argument');
			$this->output->break();
			$this->help();

			return true;
		}

		$generator = $this->arguments->context;

		$this->output->output(sprintf('invoking generator: %s', $generator));

		$generatorInstance = getGeneratorInstance(
			sprintf('\\Haku\\Console\\Commands\\Generators\\%s', mb_ucfirst($generator)),
			$this->arguments,
			$this->output
		);

		if ($generatorInstance instanceof Generator)
		{
			return $generatorInstance->run();
		}
		else {
			$this->output->error(sprintf('no such generator: %s', $generator));
		}

		return false;
	}

}
