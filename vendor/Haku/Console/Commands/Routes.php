<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

use function Haku\resolvePath;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Override;

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
			'--postman|generates a Postman collection|',
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

	protected function generatePostmanCollection(array $routes): bool
	{
		$collection = generatePostmanCollection(routes: $routes);

		$applicationName = defined('HAKU_APPLICATION_NAME') ? HAKU_APPLICATION_NAME : 'Haku';
		$targetFileName = strtolower(trim(preg_replace('#\W+#', '_', $applicationName), '_'));

		// @todo Make sure file export can be named
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
			return $this->generatePostmanCollection($routes);
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

function generatePostmanCollection(array $routes): array
{
	$applicationName = defined('HAKU_APPLICATION_NAME') ? HAKU_APPLICATION_NAME : 'Haku';

	$groupedCollection = [];

	$collection = [
		'info' => [
			'name' => sprintf("%s API", $applicationName),
			'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
		],
		'item' => [],
		'variable' => [
			[
				'key' => 'base_url',
				'value' => 'http://localhost:8000',
			]
		],
	];

	foreach($routes as $route)
	{
		$baseUrl = '{{base_url}}';
    $className = basename(str_replace('\\', '/', $route['callback'][0]));

		$path = $route['path'];
    $segments = array_filter(explode('/', $path));
		$rawPath = preg_replace('/{([^}]+)}/', '{{$1}}', $path);

		$postmanPath = array_map(function ($seg)
		{
			return preg_replace('/{([^}]+)}/', '{{$1}}', $seg);
		}, array_values($segments));

		$requestMethod = $route['method']->asString();

		$requestItem = [
			'name' => "{$requestMethod} {$route['path']}",
			'request' => [
				'method' => $requestMethod,
				'header' => [],
				'url' => [
					'raw' => "{$baseUrl}{$rawPath}",
					'host' => [$baseUrl],
					'path' => $postmanPath,
				],
			],
		];

		$groupedCollection[$className][] = $requestItem;
	}

	foreach ($groupedCollection as $controller => $items)
	{
		$collection['item'][] = [
			'name' => $controller,
			'item' => $items
		];
	}

	return $collection;
}

function collectPostmanUrlVariables(array $segments): array
{
	$variables = [];

	foreach ($segments as $segment)
	{
		if (preg_match('/^{(.+)}$/', $segment, $matches))
		{
			$variables[] = [
				'key' => $matches[1],
				'value' => '',
				'description' => "Path variable '{$matches[1]}'"
			];
		}
	}

	return $variables;
}
