<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

use function Haku\resolvePath;
use function Haku\Console\resolveArguments;

class Make extends Command
{

	#[Override]
	protected function resolveArguments(): void
	{
		$this->arguments = (object) resolveArguments(
			triggerNextAsArgument: 'make',
			triggerFieldName: 'generator',
			nextAsArgumentTriggers: [
				'spec',
			]
		);
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
		$generatorMethod = sprintf('generate%s', ucfirst($generator));

		if (!method_exists($this, $generatorMethod))
		{
			$this->output->error(sprintf('no such generator: %s', $generator));

			return false;
		}

		return call_user_func([$this, $generatorMethod]);
	}

	private function generate(
		string $templateFileName,
		string $outputFilePattern,
		?array $templateVariables = []
	): bool
	{
		$args = $this->arguments;

		// @todo Allow to set path to anything other than 'vendor'.
		$fileName = sprintf("vendor/{$outputFilePattern}", $args->{$args->generator});
		$filePath = resolvePath(...explode('/', $fileName));

		$templatePath = resolvePath(
			'private',
			'generator-templates',
			"{$templateFileName}.tmpl"
		);

		if (file_exists($filePath))
		{
			$this->output->error(
				sprintf('file already exists: %s', $fileName)
			);

			return false;
		}

		$template = file_get_contents($templatePath);

		foreach($templateVariables as $variable => $value)
		{
			$template = str_replace("%{$variable}%", strval($value), $template);
		}

		$bytesWritten = file_put_contents($filePath, $template);

		if ($bytesWritten === 0)
		{
			$this->output->error(
				sprintf('could not write file: %s', $fileName)
			);

			return false;
		}

		$this->output->success(
			sprintf('sucessfully created: %s', $fileName)
		);

		return true;
	}

	private function generateSpec(): bool
	{
		return $this->generate(
			templateFileName: 'spec',
			outputFilePattern: '%s.spec.php',
			templateVariables: [
				'testName' => $this->arguments->spec,
			],
		);
	}

}
