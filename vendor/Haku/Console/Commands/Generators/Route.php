<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Generators;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Route extends Generator
{

	public function run(): bool
	{
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

}
