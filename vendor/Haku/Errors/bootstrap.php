<?php
declare(strict_types=1);

namespace Haku\Errors;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use ErrorException;

/**
 *	Configure error reporting based on environment
 */
function configureErrorReporting(string $environment = 'production'): void
{
	switch ($environment)
	{
		case 'dev':
		case 'development':
			// Show all errors in development
			error_reporting(E_ALL);
			ini_set('display_errors', '1');
			ini_set('display_startup_errors', '1');
			break;

		case 'test':
		case 'testing':
			// Show all errors but don't display them (log instead)
			error_reporting(E_ALL);
			ini_set('display_errors', '0');
			ini_set('log_errors', '1');
			break;

		case 'prod':
		case 'production':
		default:
			// Log errors but don't display them
			error_reporting(E_ALL & ~E_DEPRECATED);
			ini_set('display_errors', '0');
			ini_set('log_errors', '1');
			break;
	}
}

/**
 *	Custom error handler that converts errors to exceptions
 */
function handleError(
	int $severity,
	string $message,
	string $file,
	int $line
): bool
{
	if (!(error_reporting() & $severity))
	{
		return false;
	}

	throw new ErrorException($message, 0, $severity, $file, $line);
}

/**
 *	Register custom error handler
 */
function registerErrorHandler(): void
{
	set_error_handler('Haku\Errors\handleError');
}

/**
 *	Log error to file
 *
 *	@param string $message
 *	@param string $level
 */
function logError(string $message, string $level = 'error'): void
{
	$logDir = HAKU_ROOT_PATH . 'private' . DIRECTORY_SEPARATOR . 'logs';

	if (!is_dir($logDir))
	{
		mkdir($logDir, 0755, true);
	}

	$logFile = $logDir . DIRECTORY_SEPARATOR . 'error.log';
	$timestamp = date('Y-m-d H:i:s');
	$env = defined('HAKU_ENV') ? HAKU_ENV : 'unknown';

	$logEntry = sprintf(
		"[%s] [%s] [%s] %s\n",
		$timestamp,
		$env,
		strtoupper($level),
		$message
	);

	error_log($logEntry, 3, $logFile);
}

/**
 *	Custom exception handler
 */
function handleException(\Throwable $exception): void
{
	$message = sprintf(
		"%s: %s in %s:%d",
		get_class($exception),
		$exception->getMessage(),
		$exception->getFile(),
		$exception->getLine()
	);

	logError($message);

	// In development, we want to see the full trace
	if (defined('HAKU_ENV') && (HAKU_ENV === 'dev' || HAKU_ENV === 'test'))
	{
		logError("Stack trace:\n" . $exception->getTraceAsString(), 'debug');
	}
}

/**
 *	Register custom exception handler
 */
function registerExceptionHandler(): void
{
	set_exception_handler('Haku\Errors\handleException');
}

/**
 *	Initialize error handling for the framework
 *
 *	@param string $environment
 */
function initialize(string $environment = 'production'): void
{
	configureErrorReporting($environment);
	registerErrorHandler();
	registerExceptionHandler();
}
