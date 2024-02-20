<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

use function Haku\resolvePath;
use function Haku\Console\resolveArguments;
use function Haku\Spl\Strings\snakeCaseFromCamelCase;

class Make extends Command
{

	protected const AvailableGenerators = [
		'spec',
		'route',
		'middleware',
		'model'
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

	private function generateRoute(): bool {
		$path = trim($this->arguments->route, '/');
		$route = $path;

		if (preg_match('/^[0-9].*?/', $route))
		{
			return false;
		}

		if (!preg_match('/([a-z0-9-\/]+)/', $route))
		{
			return false;
		}

		$segments = [];
		$parameters = [];
		$namespace = '';

		// Skip everything after optional parameter
		if (\str_contains($path, '?'))
		{
			$path = array_shift(explode('?', $path));
			$route = $path;
		}

		// Split array segments and parameters
		foreach(explode('/', $route) as $segment)
		{
			if (\str_starts_with($segment, '{'))
			{
				array_push($parameters, $segment);
			}
			else
			{
				array_push($segments, ucfirst($segment));
			}
		}

		if (count($segments) > 0)
		{
			$namespace = '\\';
			$namespace .= implode('\\', $segments);

			if ($namespace === '\\')
			{
				$namespace = '';
			}

			$namespace = str_replace('-', '_', $namespace);
		}

		// Normalize route name
		$route = $segments[count($segments) - 1];
		$routeName = implode('/', $segments);
		$routeName = str_replace('-', '_', $routeName);

		$arguments = [];

		// Parse route parameters such as {foo}, {foo:string} and {foo}?...
		foreach($parameters as $parameter)
		{
			$isOptional = \str_ends_with($parameter, '?');
			$parameter = str_replace(['{', '}', '?'], '', $parameter);
			$keepDefinedType = true;

			// @note Fixes undefined key warning
			if (!str_contains($parameter, ':'))
			{
				$keepDefinedType = false;
				$parameter .= ':mixed';
			}

			[$name, $type] = explode(':', $parameter);

			if ($name === 'id' && !$keepDefinedType)
			{
				$type = 'int';
			}

			$argument = "{$type} \${$name}";

			if ($isOptional)
			{
				$prefix = $type !== 'mixed' ? '?' : '';
				$argument = "{$prefix}{$type} \${$name} = null";
			}

			array_push($arguments, $argument);
		}

		// Cleanup path, keep full namespace as path
		$routePath = str_replace('\\', '/', $namespace);
		$routePath = str_replace('_', '-', $routePath);
		$routePath = trim(mb_strtolower($routePath), '/');

		// Remove last segment of namespace
		$namespace = implode('\\', array_slice(explode('\\', $namespace), 0, -1));

		return $this->generate(
			nameArgument: $routeName,
			targetRootPath: 'app/routes',
			templateFileName: 'route',
			outputFilePattern: '%s.php',
			templateVariables: [
				'namespace' => $namespace,
				'routePath' => $routePath,
				'routeClass' => $route,
				'arguments' => count($arguments) > 0 ? implode(', ', $arguments) : ''
			],
		);
	}

	private function generateModel(): bool
	{
		$model = $this->arguments->model;
		$segments = explode('/', $model);

		$model = array_pop($segments);
		$namespace = '';

		if (count($segments) > 0)
		{
			$namespace = '\\';
			$namespace .= implode('\\', $segments);
		}

		return $this->generate(
			targetRootPath: 'app/models',
			templateFileName: 'model',
			outputFilePattern: '%s.php',
			templateVariables: [
				'namespace' => $namespace,
				'model' => $model,
				'tableName' => snakeCaseFromCamelCase($model)
			],
		);
	}

}
