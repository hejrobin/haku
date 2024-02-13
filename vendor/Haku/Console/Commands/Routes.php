<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\{
	Command,
	Ansi
};

use function Haku\Delegation\generateApplicationRoutes;

class Routes extends Command
{

	#[Override]
	public function options(): array
	{
		return [
			'--inspect|output routes as a printed tree|',
		];
	}

	public function description(): string
	{
		return 'displays defined application routes';
	}

	protected function getMaxRoutePathIndent(array $routes): int
	{
		$paths = array_map(fn(array $route) => '/' . ltrim('/' . $route['path'], '/'), $routes);

		if (count($paths) === 0)
		{
			return 0;
		}

		return max(array_map('strlen', $paths));
	}

	protected function getMaxRouteMethodIndent(array $routes): int
	{
		$methods = array_map(fn(array $route) => $route['method']->asString(), $routes);

		if (count($methods) === 0)
		{
			return 0;
		}

		return max(array_map('strlen', $methods));
	}

	protected function getRouteMethodColor(string $method): Ansi
	{
		$color = match ($method) {
			'GET' => Ansi::White,
			'POST' => Ansi::Yellow,
			'PATCH' => Ansi::Cyan,
			'PUT' => Ansi::Cyan,
			'DELETE' => Ansi::Red,
		};

		return $color;
	}

	protected function routeDescription(
		array $route,
		int $routeIndent,
		int $methodIndent,
	): void
	{
		$spacing = '';

		$routePath = '/' . ltrim('/' . $route['path'], '/');
		$routeMethod = $route['method']->asString();
		$routeHandler = implode('::', $route['callback']);

		$actualIndent = $routeIndent - strlen($routePath);
		$spacing = \str_pad($spacing, $actualIndent, ' ', \STR_PAD_RIGHT);

		$this->output->send(
			implode(' ', [
				$this->output->format(
					\str_pad($routeMethod, $methodIndent, ' ', \STR_PAD_LEFT),
					$this->getRouteMethodColor($routeMethod)
				),
				'',
				$this->output->format($routePath, Ansi::Blue),
				$spacing,
				"—> {$routeHandler}",
			])
		);

	}

	public function invoke(): bool
	{
		$routes = generateApplicationRoutes();
		$args = $this->arguments->arguments;

		if (array_key_exists('inspect', $args))
		{
			print_r($routes);
			return true;
		}

		$routeIndent = $this->getMaxRoutePathIndent($routes);
		$methodIndent = $this->getMaxRouteMethodIndent($routes);

		$this->output->info('available routes:');

		foreach ($routes as $route)
		{
			$this->routeDescription($route, $routeIndent, $methodIndent);
		}

		return true;
	}

}
