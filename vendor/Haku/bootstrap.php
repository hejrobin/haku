<?php
declare(strict_types=1);

namespace Haku;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use RegexIterator;
use RecursiveRegexIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

use \Haku\Exceptions\VendorException;

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
 *	Removes exessive slashes from a string.
 */
function cleanPath(string $unresolvedPath): string
{
	return trim(preg_replace('/[\/]+/', '/', $unresolvedPath), '/');
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
		$includeRootPath = '';

		// Resolve app specific namespace
		$segments = explode('\\', $unresolvedNamespace);
		$fileName = array_pop($segments) . '.php';

		$segments = array_map('mb_strtolower', $segments);
		$pathSegments = [...$segments, $fileName];

		// Converts App/Routes/Home to app/routes/Home.php
		$namespaceDirectoryPath = implode(DIRECTORY_SEPARATOR, $pathSegments);
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

function autoloadResolver()
{
	/* Set up "magic" vendor specific autoloading */

	\spl_autoload_register(
		fn(string $namespace) => require_once resolveVendorNamespacePath($namespace),
		throw: true,
		prepend: true,
	);

	/* Load every bootstrap file in every Haku module */
	$regexDirectoryIterator = new RegexIterator(
		new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				'./vendor/'
			),
		),
		'/bootstrap\.php/',
		RecursiveRegexIterator::GET_MATCH,
	);

	$includePaths = array_keys(iterator_to_array($regexDirectoryIterator));

	foreach ($includePaths as $includePath)
	{
		require_once resolvePath($includePath);
	}
}

function loadEnvironment(string $fallbackEnvironment = 'dev')
{
	global $argv;

	if (isset($argv[1]) && $argv[1] === 'test')
	{
		$fallbackEnvironment = 'test';
	}

	config('HAKU_ENVIRONMENT', $fallbackEnvironment);

	$configFilePath = sprintf('config.%s.php', HAKU_ENVIRONMENT);

	if (!file_exists($configFilePath))
	{
		header('content-type: text/plain');

		echo sprintf("panic: no such environment: %s\n", HAKU_ENVIRONMENT);
		echo sprintf("run 'php haku init --%s' in your terminal\n", HAKU_ENVIRONMENT);

		exit -1;
	}

	require_once resolvePath($configFilePath);
}

function loadBootstrap()
{
	require_once resolvePath('bootstrap.php');
}

/**
 *	Returns information found in manifest.json
 */
function manifest(): object
{
	return json_decode(
		file_get_contents(
			resolvePath('manifest.json')
		)
	);
}

/**
 *	Returns Kernel instance, or initialized instance inside factory if instanceName is passed.
 *
 *	@throws \Haku\Exceptions\VendorException
 *
 *	@return object
 */
function haku(?string $instanceName = null): object
{
	$kernel = Core\Kernel::getInstance();

	if ($instanceName)
	{
		if (!$kernel->has($instanceName))
		{
			throw new VendorException(sprintf('Instance %s not initialized.', $instanceName));
		}

		return $kernel->get($instanceName);
	}

	return $kernel;
}

/**
 *	Creates a config variable, attempts to get value from environment first, otherwise fallback.
 */
function config(string $variable, string $fallback): void
{
	$value = getenv($variable);
	$value ??= $fallback;

	if(!defined($variable))
	{
		define($variable, $fallback);
	}
}

/**
 *	Queries do not have an autoload feature set up (on purpose), load these manually.
 *
 *	@param string $fileName
 *
 *	@return void
 */
function loadApplicationQueries(string $fileName)
{
	require_once resolvePath('app/queries/' .  $fileName . '.php');
}

/**
 *	Logs to PHP dev-server console.
 *
 *	@param string $message
 *
 *	@return void
 */
function log(string $message): void
{
	if (php_sapi_name() === 'cli-server')
	{
		error_log('<Haku> ' . $message);
	}
}
