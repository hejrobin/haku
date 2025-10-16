<?php
declare(strict_types=1);

namespace Haku\Generic\Url;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Generic\Strings\hyphenate;

function resolve(bool $omitRequestPath = false): string
{
	$sslSuffix = (empty($_SERVER['HTTPS']) === true
			? ''
			: strtolower($_SERVER['HTTPS']) === 'on')
		? 's'
		: '';

	$protocol = strtolower($_SERVER['SERVER_PROTOCOL']);
	$protocol = substr($protocol, 0, strpos($protocol, '/')) . $sslSuffix;

	$port = intval($_SERVER['SERVER_PORT']) === 80 ? '' : ':' . $_SERVER['SERVER_PORT'];

	$resolvedUrl = implode('', [
		$protocol,
		'://',
		$_SERVER['SERVER_NAME'],
		$port,
		$_SERVER['REQUEST_URI'],
	]);

	if ($omitRequestPath === true)
	{
		$resolvedUrl = implode('', [
			$protocol,
			'://',
			$_SERVER['SERVER_NAME'],
			$port,
			dirname($_SERVER['SCRIPT_NAME']),
		]);
	}

	return $resolvedUrl;
}

function path(?string $unresolvedUri = null): string
{
	if ($unresolvedUri === null)
	{
		$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
		$scriptName = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));

		$segments = array_diff_assoc($requestUri, $scriptName);
		$segments = array_filter($segments);

		if (empty($segments) === true)
		{
			return '/';
		}

		$uriPath = implode('/', $segments);
		$uriPath = parse_url($uriPath, PHP_URL_PATH);

		return $uriPath ?? '/';
	}

	return preg_replace('#/+#', '/', trim(hyphenate($unresolvedUri), '/'));
}
