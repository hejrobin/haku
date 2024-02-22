<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Generators;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Middleware extends Generator
{

	public function run(): bool
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

}
