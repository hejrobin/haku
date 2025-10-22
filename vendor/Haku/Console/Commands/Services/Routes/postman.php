<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Services\Routes;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Generates a Postman collection based on application routes.
 *
 *	@return array[]|array{info: array{name: string, schema: string, item: array, variable: array}}
 */
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

/**
 *	Collects variables from url.
 *
 *	@param array $segments
 *
 *	@return array{description: string, key: mixed, value: string[]}
 */
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
