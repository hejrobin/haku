<?php
declare(strict_types=1);

namespace Haku\Delegation;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use ReflectionClass;
use ReflectionMethod;
use ReflectionAttribute;

use RegexIterator;
use RecursiveRegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

use Haku\Exceptions\FrameworkException;

use Haku\Http\{
	Request,
	Headers,
	Method,
	Status,
	Exceptions\StatusException
};

use function Haku\{
	haku,
	cleanPath,
	resolvePath
};

use function Haku\Generic\Strings\{
	camelCaseFromSnakeCase,
	snakeCaseFromCamelCase,
};

use function Haku\Generic\Arrays\find;

function loadApplicationRoutes(): array {
	$routeClassNames = [];
	$pathResolveRegExp = '/^.+[A-Z][a-z]+\.php$/';

	$regexDirectoryIterator = new RegexIterator(
		new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				'./app/routes/'
			),
		),
		$pathResolveRegExp,
		RecursiveRegexIterator::GET_MATCH,
	);

	$includePaths = array_keys(iterator_to_array($regexDirectoryIterator));

	foreach ($includePaths as $includePath)
	{
		preg_match('/(\w+)\.php$/', $includePath, $match);
		$routeClassName = str_ireplace('.php', '', $match[0]);

		$namespace = str_replace(['./', '.php'], '', $includePath);

		$namespaceSegments = array_map(fn(string $segment) => ucfirst($segment), explode('/', $namespace));
		$resolvedNamespace = implode('\\', $namespaceSegments);

		array_push($routeClassNames, $resolvedNamespace);

		require_once resolvePath(str_replace('./', '', $includePath));
	}

	return $routeClassNames;
}

/**
 *	Iterates recursively through app/routes and builds a parseable routes object.
 */
function generateApplicationRoutes(): array
{
	$routes = [];
	$controllers = loadApplicationRoutes();

	foreach ($controllers as $controller)
	{
		$ref = new ReflectionClass($controller);

		$parent = parseRouteAttributes($ref->getAttributes());

		foreach ($ref->getMethods() as $method)
		{
			$route = [];
			$attrs = $method->getAttributes();

			foreach ($attrs as $attr)
			{
				$route = $route + parseRouteAttribute($attr, $method);
			}

			if ($parent)
			{
				if (array_key_exists('path', $parent))
				{
					$route['path'] = cleanPath("/{$parent['path']}/{$route['path']}");
				}

				if (array_key_exists('middlewares', $parent))
				{
					$partentMiddlewares = $parent['middlewares'] ?? [];
					$routeMiddlewares = $route['middlewares'] ?? [];

					$route['middlewares'] = [
						...$partentMiddlewares,
						...$routeMiddlewares
					];
				}
			}

			// Normalize route middleware names
			$route['middlewares'] = array_map('\\Haku\\Delegation\\normalizeMiddlewarePathName', $route['middlewares'] ?? []);

			// Generate route pattern regex
			$route['pattern'] = pathToRegex($route['path']);

			array_push($routes, $route);
		}

	}

	return $routes;
}

/**
 *	Converts a route path into regex.
 */
function pathToRegex(
	string $path
): string
{

	$pattern = '';
	$segments = explode('/', $path);

	foreach($segments as $segment)
	{
		preg_match(
			'~{(?<parameter>([\w\-_%]+))(?:\:?(?<type>(\w+)))?}(?:(?<optional>\?))?~ix',
			$segment,
			$match,
		);

		// Path includes a paramerer ( "{foo}" )
		if (array_key_exists('parameter', $match))
		{
			$prefix = '';
			$suffix = '';

			$innerPattern = '([\w\-_%]+)';
			$parameter = $match['parameter'];

			// Parameter name ends with "id"
			if (str_ends_with(strtolower($match['parameter']), 'id'))
			{
				$innerPattern = '(\d+)';
			}

			// Path has a type, i.e: "{foo:number}"
			if (array_key_exists('type', $match))
			{
				if ($match['type'] === 'number')
				{
					$innerPattern = '(\d+)';
				}
			}

			// Parameter is optional, ending with a question mark: "{foo}?"
			if (array_key_exists('optional', $match))
			{
				$prefix = '(?:';
				$suffix = ')?';
			}

			$pattern .= "{$prefix}(?<{$parameter}>{$innerPattern}){$suffix}";
		}
		else
		{
			// No parameters in path, match as defined
			$pattern .= "({$segment})/";
		}
	}

	$pattern = trim($pattern, '/');

	return "~^{$pattern}$~ix";
}

/**
 *	Parses route specific attribute.
 *
 *	#[Route], #[Uses], #[WithStatus], #[WithHeaders],
 */
function parseRouteAttribute(
	ReflectionAttribute $attribute,
	ReflectionMethod $method = null
): array
{
	$ref = $attribute->newInstance();

	$parsed = [];

	if ($ref instanceof Route)
	{
		$name = $ref->getName();

		if (empty($name) && isset($method))
		{
			$niceName = str_replace('App\\Routes\\', '', $method->class);
			$niceName .= ucfirst($method->name);

			$name = snakeCaseFromCamelCase($niceName);
		}

		$parsed = $parsed + [
			'name' => $name,
			'path' => $ref->getPath(),
			'pattern' => '',
			'method' => $ref->getMethod(),
			'callback' => []
		];

		if ($method)
		{
			$parsed['callback'] = [
				$method->class,
				$method->name
			];
		}

	}

	if ($ref instanceof Uses)
	{
		$parsed['middlewares'] = $ref->getMiddlewares();
	}

	if ($ref instanceof WithStatus)
	{
		$parsed['httpStatus'] = $ref->getStatusCode();
	}

	if ($ref instanceof WithHeaders)
	{
		if (array_key_exists('httpHeaders', $parsed) === false)
		{
			$parsed['httpHeaders'] = [];
		}

		$parsed['httpHeaders'] = $parsed['httpHeaders'] + $ref->getHeaders();
	}

	return $parsed;
}

/**
 *	Parses each route specific attribute.
 *
 *	@see {parseRouteAttribute}
 */
function parseRouteAttributes(
	array $attributes,
	ReflectionMethod $method = null
): array
{
	$parsed = [];

	foreach ($attributes as $attribute)
	{
		$parsed = $parsed + parseRouteAttribute($attribute, $method);
	}

	return $parsed;
}

/**
 *	Converts middlewares from "foo" to "App\Middlewares\Foo"
 */
function normalizeMiddlewarePathName(string $unresolved): string
{
	$namespace = ['App', 'Middlewares'];

	$parts = explode('/', $unresolved);
	$parts = array_map(fn($part) => ucfirst(camelCaseFromSnakeCase($part)), $parts);

	return implode('\\', [...$namespace, ...$parts]);
}

/**
 *	Attempts to find route definition based on URL path and processes the request.
 *
 *	@throws Haku\Exceptions\FrameworkException
 * 	@throws Haku\Http\Exceptions\StatusException
 *
 *	@return [Haku\Http\Request, Haku\Http\Message, Haku\Http\Headers]
 */
function delegate(string $path, Headers $headers): array
{
	$routes = generateApplicationRoutes();

	if (count($routes) === 0)
	{
		throw new FrameworkException('No application routes defined');
	}

	$requestMethod = Method::resolve();

	$foundRoute = find($routes, function ($route) use ($path, $requestMethod)
	{
		// @note Check with trailing slash to also match optional parameters properly
		$hasPatternMatch =
			preg_match($route['pattern'], cleanPath($path)) === 1 ||
			preg_match($route['pattern'], cleanPath($path) . '/') === 1;

		$hasMethodMatch = $route['method'] === $requestMethod;

		if ($hasPatternMatch && !$hasMethodMatch)
		{
			// 405 Method Not Allowed
			throw new StatusException(405);
		}

		return $hasPatternMatch && $hasMethodMatch;
	});

	if (!$foundRoute)
	{
		// 404 Not Found
		throw new StatusException(404);
	}

	if (array_key_exists('httpStatus', $foundRoute))
	{
		$headers->status(Status::from($foundRoute['httpStatus']));
	}

	if (array_key_exists('httpHeaders', $foundRoute))
	{
		$headers->append($foundRoute['httpHeaders']);
	}

	preg_match($foundRoute['pattern'], $path, $matches);

	$foundRoute['parameters'] = array_filter(
		$matches,
		'is_string',
		\ARRAY_FILTER_USE_KEY
	);

	$request = Request::from($foundRoute, $headers);

	return $request->process();
}
