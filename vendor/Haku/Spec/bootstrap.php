<?php
declare(strict_types=1);

namespace Haku\Spec;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Closure;
use RegexIterator;
use RecursiveRegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

use Haku\Http\{
	Request,
	Headers,
	Method,
	Message,
	Status,
	Messages\Json,
};

use Haku\Spec\Expectations\{
	Expectations,
	ExpectationResult
};

use function Haku\cleanPath;
use function Haku\Delegation\generateApplicationRoutes;
use function Haku\Database\isConfigured as isDatabaseConfigured;

function spec(
	string $description,
	Closure $container,
	array $tags = []
): void
{
	// Auto-exclude database tests if database is not configured
	if (in_array('database', $tags, true) && !isDatabaseConfigured())
	{
		// Skip this spec entirely if it requires database but database is not configured
		return;
	}

	Runner::getInstance()->describeSpec($description, $tags);

	$container();
}

function describe(
	string $description,
	Closure $container
): void
{
	Runner::getInstance()->registerTest(new Test($description));

	$container();
}

function it(
	string $description,
	Closure $container
): void
{
	Runner::getInstance()->registerTestCase($description, $container);
}

function beforeAll(
	Closure $beforeAll
): void {
	Runner::getInstance()->beforeAll($beforeAll);
}

function afterAll(
	Closure $afterAll
): void {
	Runner::getInstance()->afterAll($afterAll);
}

function before(
	Closure $before
): void {
	Runner::getInstance()->before($before);
}

function after(
	Closure $after
): void {
	Runner::getInstance()->after($after);
}

function beforeEach(
	Closure $beforeEach
): void {
	Runner::getInstance()->beforeEach($beforeEach);
}

function afterEach(
	Closure $afterEach
): void {
	Runner::getInstance()->afterEach($afterEach);
}

/**
 *	Returns a new expectation instance.
 */
function expect(
	mixed $actual
): Expectations
{
	return new Expectations($actual);
}

/**
 *	Validates all {@see Haku\Spec\expect}.
 */
function expectAll(
	ExpectationResult ...$expectations
): ExpectationResult {
	$failedExpectations = [];

	foreach ($expectations as $index => $expectation)
	{
		if ($expectation->success === false)
		{
			$failedExpectations[] = "at index {$index}: {$expectation->message}";
		}
	}

	return new ExpectationResult(
		count($failedExpectations) === 0,
		implode(', ', $failedExpectations)
	);
}

/**
 *	Loads all *.spec.php files from root recursively.
 */
function loadSpecTests(
	?string $only = '',
	?string $omit = '',
	?array $excludePaths = null,
): int
{
	$pathResolveRegExp = '/^.+[A-Za-z]+\.spec\.php$/';

	$regexDirectoryIterator = new RegexIterator(
		new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				'./'
			),
		),
		$pathResolveRegExp,
		RecursiveRegexIterator::GET_MATCH,
	);

	$includePaths = array_keys(iterator_to_array($regexDirectoryIterator));

	if (is_array($excludePaths) && count($excludePaths) > 0)
	{
		foreach ($excludePaths as $exclude)
		{
			$includePaths = array_filter(
				$includePaths,
				function (string $path) use ($exclude)
				{
					return stripos($path, $exclude) > -1;
				}
			);
		}
	}

	if (is_string($only) && strlen($only) > 0)
	{
		$keywords = explode(',', $only);
		$tmpIncludePaths = [];

		foreach ($keywords as $keyword)
		{
			$tmpIncludePaths = array_merge(
				$tmpIncludePaths,
				array_filter($includePaths, function (string $path) use ($keyword)
				{
					return str_contains(strtolower($path), mb_trim(strtolower($keyword)));
				})
			);
		}

		$includePaths = $tmpIncludePaths;
	}

	if (is_string($omit) && strlen($omit) > 0)
	{
		$keywords = explode(',', $omit);
		$tmpIncludePaths = [];

		foreach ($keywords as $keyword)
		{
			$tmpIncludePaths = array_merge(
				$tmpIncludePaths,
				array_filter($includePaths, function (string $path) use ($keyword)
				{
					return !str_contains(strtolower($path), mb_trim(strtolower($keyword)));
				})
			);
		}

		$includePaths = $tmpIncludePaths;
	}

	foreach ($includePaths as $includePath)
	{
		require_once HAKU_ROOT_PATH . DIRECTORY_SEPARATOR . $includePath;
	}

	return count($includePaths);
}

class RouteExpectationResult
{

	public function __construct(
		public Request $request,
		public Message $response,
		public Headers $headers,
		public Status $status,
	) {}

}

/**
 *	Invokes a specific route and returns an object with request, response, headers and status data.
 *
 *	@param string $path
 *	@param \Haku\Http\Method $requestMethod
 *
 *	@return object
 */
function route(
	string $path,
	Method $requestMethod = Method::Get,
	array $additionalHeaders = []
): RouteExpectationResult
{
	$routes = generateApplicationRoutes();

	$headers = new Headers([
		'Content-Type' => 'application/json',
	]);

	$headers->append($additionalHeaders);

	$foundRoute = array_find($routes, function ($route) use ($path, $requestMethod)
	{
		// @note Check with trailing slash to also match optional parameters properly
		$hasPatternMatch =
			preg_match($route['pattern'], cleanPath($path)) === 1 ||
			preg_match($route['pattern'], cleanPath($path) . '/') === 1;

		$hasMethodMatch = $route['method'] === $requestMethod;

		if ($hasPatternMatch && !$hasMethodMatch)
		{
			$route['httpStatus'] = 405;

			return true;
		}

		return $hasPatternMatch && $hasMethodMatch;
	});

	if (!$foundRoute)
	{
		$noOpClass = new class {
			public function noOp()
			{
				return Json::from([]);
			}
		};

		$foundRoute = [
			'name' => 'error',
			'path' => cleanPath($path),
			'pattern' => '~^(error)$~ix',
			'method' => $requestMethod,
			'callback' => [$noOpClass, 'noOp'],
			'middlewares' => [],
			'httpStatus' => 404,
		];
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

	[$request, $response, $headers] = $request->process();

	return new RouteExpectationResult(
		request: $request,
		response: $response,
		headers: $headers,
		status: $headers->getStatus(),
	);
}
