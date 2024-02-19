<?php
declare(strict_types=1);

namespace Haku;

error_reporting(E_ALL & ~E_NOTICE);

define('HAKU_ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('HAKU_PHP_VERSION', '8.3.0');

if (version_compare(PHP_VERSION, HAKU_PHP_VERSION, '<')) {
	echo sprintf("Haku requires PHP %s, version %s installed.", HAKU_PHP_VERSION, PHP_VERSION);
	exit;
}

use Throwable;

use Haku\Exceptions\FrameworkException;

use Haku\Http\{
	Status,
	Headers,
	Message,
	Messages\Json,
	Exceptions\StatusException,
};

use function Haku\{
	resolvePath,
	config
};

use function Haku\Spl\Url\path;
use function Haku\Delegation\delegate;

/* @willResolve Haku\Http\Message */
$__outputBuffer = null;

/* @willResolve Haku\Http\Headers */
$__outputHeaders = null;

try
{
	require_once implode(DIRECTORY_SEPARATOR, [
		rtrim(HAKU_ROOT_PATH, DIRECTORY_SEPARATOR),
		'vendor', 'Haku', 'bootstrap.php'
	]);

	autoloadResolver();
	loadEnvironment();

	$__outputHeaders = new Headers([
		'Content-Type' => 'application/json'
	]);

	ob_start();

	[$request, $response, $headers] = delegate(path(), $__outputHeaders);

	if (!$response || $response?->size() === 0)
	{
		throw new StatusException(500);
	}

	$__outputHeaders = $headers;

	echo $response;

	$__outputBuffer = ob_get_clean();
}
catch(StatusException $throwable)
{
	$__outputHeaders->status(
		Status::from($throwable->getCode())
	);

	$__outputBuffer = Json::from([
		'code' => $throwable->getCode(),
		'error' => $throwable->getMessage(),
	]);
}
catch(Throwable $throwable)
{
	$__outputHeaders->status(
		Status::from(500)
	);

	$__outputBuffer = Json::from([
		'code' => 500,
		'error' => $throwable->getMessage(),
	]);
}
finally
{
	if ($__outputHeaders instanceof Headers)
	{
		$__outputHeaders->send();
	}

	echo $__outputBuffer;

	exit;
}
