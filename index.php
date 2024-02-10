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

/* @note Capture output, resolve it and then output it */
$__outputBuffer = null;

try
{
	require_once implode(DIRECTORY_SEPARATOR, [
		rtrim(HAKU_ROOT_PATH, DIRECTORY_SEPARATOR),
		'vendor', 'Haku', 'bootstrap.php'
	]);

	hakuAutoloadResolver();

	ob_start();

	echo '{ "message": "Hello World!" }';

	$__outputBuffer = ob_get_clean();
}
catch(\Throwable $throwable)
{
	$__outputBuffer = $throwable->getMessage();
}
finally
{
	echo $__outputBuffer;
	exit;
}
