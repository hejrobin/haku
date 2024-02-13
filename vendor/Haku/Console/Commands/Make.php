<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

use function Haku\resolvePath;
use function Haku\Console\resolveArguments;
use function Haku\Spl\Strings\snakeCaseFromCamelCase;

function namespaceToRoutePath(string $unresolved): string
{
	$segments = explode('\\', $unresolved);
	$segments = array_map(fn(string $segment) => snakeCaseFromCamelCase($segment), $segments);

	$hyphenated = str_replace('_', '-', implode('/', $segments));

	return trim($hyphenated, '/');
}

class Make extends Command
{

	protected const AvailableGenerators = [
		'spec',
		'route',
		'middleware',
	];

	#[Override]
	protected function resolveArguments(): void
	{
		$this->arguments = (object) resolveArguments(
			triggerNextAsArgument: 'make',
			triggerFieldName: 'generator',
			nextAsArgumentTriggers: self::AvailableGenerators,
		);
	}

	#[Override]
	public function options(): array
	{
		return [
			sprintf('generator|generator name|%s', implode(', ', self::AvailableGenerators)),
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
		?array $templateVariables = [],
		string $targetRootPath = 'vendor',
		?string $nameArgument = null,
	): bool
	{
		$args = $this->arguments;

		if (is_null($nameArgument) || empty($nameArgument))
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

	private function generateSpec(): bool
	{
		return $this->generate(
			templateFileName: 'spec',
			outputFilePattern: '%s.spec.php',
			templateVariables: [
				'spec' => $this->arguments->spec,
			],
		);
	}

	private function generateMiddleware(): bool
	{
		$middleware = $this->arguments->middleware;
		$segments = explode('/', $middleware);

		$middleware = array_pop($segments);
		$namespace = '';

		if (count($segments) > 0)
		{
			$namespace = '\\';
			$namespace .= implode('\\', $segments);
		}

		return $this->generate(
			targetRootPath: 'app/middlewares',
			templateFileName: 'middleware',
			outputFilePattern: '%s.php',
			templateVariables: [
				'namespace' => $namespace,
				'middleware' => $middleware,
			],
		);
	}

	private function generateRoute(): bool
	{
		$route = $this->arguments->route;

		$segments = explode('/', $route);
		$segments = array_map(fn(string $segment) => ucfirst($segment), $segments);

		$route = array_pop($segments);
		$namespace = '';

		if (count($segments) > 0)
		{
			$namespace = '\\';
			$namespace .= implode('\\', $segments);
		}

		if (array_key_exists('root', $this->arguments->arguments))
		{
			$name = implode('/', $segments);
		}
		else
		{
			$name = implode('/', [...$segments, $route]);
		}

		return $this->generate(
			nameArgument: $name,
			targetRootPath: 'app/routes',
			templateFileName: 'route',
			outputFilePattern: '%s.php',
			templateVariables: [
				'namespace' => $namespace,
				'routePath' => namespaceToRoutePath($name),
				'routeClass' => $route,
			],
		);
	}

}
