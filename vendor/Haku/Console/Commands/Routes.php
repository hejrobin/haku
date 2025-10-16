<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Override;

use Haku\Console\{
	Command,
	Ansi
};

use function Haku\resolvePath;

use function Haku\Delegation\generateApplicationRoutes;

use function Haku\Console\Commands\Services\Routes\generatePostmanCollection;

/**
 *	A command that list all available routes. Can return full route definitions or create a postman collection file.
 */
class Routes extends Command
{

	#[Override]
	public function options(): array
	{
		return [
			'--inspect|output routes as a printed tree|',
			'--postman|generates a Postman collection|',
		];
	}

	public function description(): string
	{
		return 'displays defined application routes';
	}

	protected function getMaxRoutePathIndent(array $routes): int
	{
		$paths = array_map(fn(array $route) => '/' . mb_ltrim('/' . $route['path'], '/'), $routes);

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

		$routePath = '/' . mb_ltrim('/' . $route['path'], '/');
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

	protected function invokePostmanGeneration(array $routes): bool
	{
		$collection = generatePostmanCollection(routes: $routes);

		$applicationName = defined('HAKU_APPLICATION_NAME') ? HAKU_APPLICATION_NAME : 'Haku';
		$targetFileName = strtolower(mb_trim(preg_replace('#\W+#', '_', $applicationName), '_'));

		$targetFilePath = resolvePath(sprintf('private/postman_%s.json', $targetFileName));
		$json = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		$didWrite = file_put_contents($targetFilePath, $json);

		if ($didWrite)
		{
			$this->output->info(sprintf('generated postman collection >> %s', $targetFilePath));
		}

		return $didWrite !== false;
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

		if (array_key_exists('postman', $args))
		{
			return $this->invokePostmanGeneration($routes);
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
