<?php
declare(strict_types=1);

namespace Haku;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use \Exception;
use \RegexIterator;
use \RecursiveRegexIterator;
use \RecursiveIteratorIterator;
use \RecursiveDirectoryIterator;

/**
 *	Alias class for Exception
 *
 *	@see https://www.php.net/manual/en/class.exception.php
 */
class VendorException extends Exception {}

/**
 *	Resolves relative path into an absolute path with safe directory separator.
 */
function resolvePath(string ...$pathSegments): string
{
	return implode(DIRECTORY_SEPARATOR, [
		rtrim(HAKU_ROOT_PATH, DIRECTORY_SEPARATOR),
		...$pathSegments
	]);
}

/**
 *	Attempts to resolve namespace specific imports;
 *
 *	Anything under /vendor/ where namespace matches file structure, case sensitive.
 *	Application specific namespaces are lowecased except for file name, e.g "App\Routes\Home" becomes "app/routes/Home"
 *
 *	@throws VendorException
 */
function resolveVendorNamespacePath(string $unresolvedNamespace): string
{
	// Assume exact match, convert Package\Sub\Module to Package/Sub/Module.php
	$namespaceDirectoryPath = str_replace('\\', DIRECTORY_SEPARATOR, $unresolvedNamespace);
	$namespaceDirectoryPath .= '.php';

	$includeRootPath = 'vendor';

	// Handle app-specific namespaces
	if (str_starts_with($unresolvedNamespace, 'App'))
	{
		$includeRootPath = 'app';

		// Resolve app specific namespace
		$segments = explode('\\', $unresolvedNamespace);
		$fileName = array_pop($segments) . '.php';

		$segments = array_map('mb_strtolower', $segments);
		$pathSegments = [...$segments, $fileName];

		// Converts App/Routes/Home to app/routes/Home.php
		$namespaceDirectoryPath = resolvePath(...$pathSegments);
	}

	$resolvedFilePath = resolvePath($includeRootPath, $namespaceDirectoryPath);
	$resolvedFilePath = str_replace('//', DIRECTORY_SEPARATOR, $resolvedFilePath);

	$resolvedFilePathExists = file_exists($resolvedFilePath) === true;

	if (!$resolvedFilePathExists)
	{
		throw new VendorException(sprintf(
			'Could not resolve %s into %s, file not found.',
			$unresolvedNamespace,
			$resolvedFilePath
		));
	}

	return $resolvedFilePath;
}

/**
 *	Attempts to load bootstrap file for a specfic vendor.
 *	Resolves to vendor/VendorName/bootstrap.php
 *
 *	@throws VendorException
 */
function loadVendorBootstrap(
	string $vendorName,
	bool $throwOnFileNotFound = false
): bool
{
	$bootstrapFilePath = resolvePath('vendor', $vendorName, 'bootstrap.php');
	$bootstrapFileExists = file_exists($bootstrapFilePath) === true;

	if ($throwOnFileNotFound && !$bootstrapFileExists)
	{
		throw new VendorException(sprintf(
			'Bootstrap file for %s does not exist.',
			$vendorName
		));
	}

	if ($bootstrapFileExists)
	{
		require_once $bootstrapFilePath;
		return true;
	}

	return false;
}
