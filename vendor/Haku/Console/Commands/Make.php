<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

use Haku\Console\Commands\Generators\{
	Generator,
	AvailableGenerators
};


use function Haku\Console\resolveArguments;
use function Haku\Console\Commands\Generators\getGeneratorInstance;

class Make extends Command
{

	#[Override]
	protected function resolveArguments(): void
	{
		$this->arguments = (object) resolveArguments(
			triggerNextAsArgument: 'make',
			triggerFieldName: 'generator',
			nextAsArgumentTriggers: AvailableGenerators::list(),
		);
	}

	#[Override]
	public function options(): array
	{
		return [
			sprintf('generator|generator name|%s', implode(', ', AvailableGenerators::list())),
		];
	}

	public function description(): string
	{
		return 'code generator tools';
	}

	public function invoke(): bool
	{
		if (!property_exists($this->arguments, 'generator'))
		{
			$this->output->error('make requires a generator argument');

			return false;
		}

		$generator = $this->arguments?->generator;

		$this->output->output(sprintf('invoking generator: %s', $generator));

		$generatorInstance = getGeneratorInstance(
			sprintf('\\Haku\\Console\\Commands\\Generators\\%s', ucfirst($generator)),
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
