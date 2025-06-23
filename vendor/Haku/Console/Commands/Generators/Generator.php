<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Generators;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Output;

use function Haku\resolvePath;

/**
 *	An abstract class that handles code generation for "haku make".
 *	A generator requires a *.tmpl file to work.
 */
abstract class Generator
{

	public function __construct(
		protected object $arguments,
		protected Output $output,
	) {}

	/**
	 *	Attempts to generate code from a template.
	 *
	 *	@param string $templateFileName
	 *	@param string $outputFilePattern
	 *	@param mixed $templateVariables
	 *	@param string $targetRootPath
	 *	@param mixed $nameArgument
	 *
	 *	@return bool
	 */
	protected function generate(
		string $templateFileName,
		string $outputFilePattern,
		?array $templateVariables = [],
		string $targetRootPath = 'vendor',
		?string $nameArgument = null,
	): bool
	{
		$args = $this->arguments;

		if ($nameArgument === null || empty($nameArgument))
		{
			$nameArgument = ucfirst($args->{$args->generator});
		}

		$fileName = sprintf("{$targetRootPath}/{$outputFilePattern}", $nameArgument);
		$filePath = resolvePath(...explode('/', $fileName));

		$directoryPath = str_ireplace(basename($filePath), '', $filePath);

		$templatePath = resolvePath(
			'private',
			'generator-templates',
			"{$templateFileName}.tmpl"
		);

		if (!is_dir($directoryPath))
		{
			$didCreate = mkdir(directory: $directoryPath, recursive: true);

			if (!$didCreate)
			{
				$this->output->error('could not create path');

				return false;
			}
		}

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

	abstract public function run(): bool;

}

