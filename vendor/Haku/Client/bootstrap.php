<?php
declare(strict_types=1);

/**
 *	@package Haku\Client
 *
 *	Client package has simple functionality to detect non-identifiable client platform information based on user agent.
 *
 *	> [!NOTE]
 *	> While convenient, none of these methods should be concidered as factual, since clients can send _any_ user agent.
 */
namespace Haku\Client;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Attempts to detect client operating system based on user-agent.
 *
 *	@param string $userAgent
 *
 *	@return string
 */
function detectClientOperatingSystem(string $userAgent): string
{
	$ua = mb_strtolower($userAgent);

	$os = 'Unknown';

	if (strpos($ua, 'iphone') !== false)
	{
		$os = 'iOS';
	}
	elseif (strpos($ua, 'ipad') !== false)
	{
		$os = 'iPadOS';
	}
	elseif (
		strpos($ua, 'macintosh') !== false &&
		strpos($ua, 'mobile') !== false
	) {
		$os = 'iPadOS';
	}
	elseif (strpos($ua, 'android') !== false)
	{
		$os = 'Android';
	}
	elseif (strpos($ua, 'windows nt') !== false)
	{
		$os = 'Windows';
	}
	elseif (strpos($ua, 'mac os x') !== false)
	{
		$os = 'macOS';
	}
	elseif (strpos($ua, 'linux') !== false)
	{
		$os = 'Linux';
	}

	return $os;
}

/**
 *	Attempts to detect client browser based on user-agent.
 *
 *	@param string $userAgent
 *
 *	@return string
 */
function detectClientBrowser(string $userAgent): string
{
	$ua = mb_strtolower($userAgent);

	// @important Order of these matter, *do not edit*
	$browserList = [
		'torbrowser' => 'Tor',
		'duckduckgo' => 'DuckDuckGo',
		'arc' => 'Arc',
		'firefox' => 'Firefox',
		'edg' => 'Edge',
		'opr' => 'Opera',
		'opera' => 'Opera',
		'brave' => 'Brave',
		'vivaldi' => 'Vivaldi',
		'chrome' => 'Chrome',
		'safari' => 'Safari'
	];

	$browser = 'Unknown';

	foreach ($browserList as $key => $name)
	{
		if (strrpos($ua, $key) !== false)
		{
			$browser = $name;
			break;
		}
	}

	return $browser;
}

/**
 *	Attempts to detect client device based on user-agent.
 *
 *	@param string $userAgent
 *
 *	@return string
 */
function detectClientDevice(string $userAgent): string
{
	$ua = mb_strtolower($userAgent);

	$device = 'Desktop';

	if (
		strrpos($ua, 'ipad') !== false ||
		strrpos($ua, 'tablet') !== false
	) {
		$device = 'Tablet';
	}
	else if (
		strrpos($ua, 'mobile') !== false ||
		strrpos($ua, 'iphone') !== false ||
		strrpos($ua, 'android') !== false
	) {
		$device = 'Mobile';
	}

	return $device;
}
