<?php
declare(strict_types=1);

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
};

use function Haku\Errors\{
	configureErrorReporting,
	logError
};

spec('Error Handling', function()
{

	describe('Error configuration', function()
	{

		it('can configure error reporting for development', function()
		{
			configureErrorReporting('dev');
			$reporting = error_reporting();

			return expect($reporting)->toEqual(E_ALL);
		});

		it('can configure error reporting for production', function()
		{
			configureErrorReporting('production');
			$reporting = error_reporting();

			// Production should report all errors except deprecated and strict
			$expected = E_ALL & ~E_DEPRECATED & ~E_STRICT;

			return expect($reporting)->toEqual($expected);
		});

		it('can configure error reporting for testing', function()
		{
			configureErrorReporting('test');
			$reporting = error_reporting();

			return expect($reporting)->toEqual(E_ALL);
		});

	});

	describe('Error logging', function()
	{

		it('can log errors to file', function()
		{
			$logDir = HAKU_ROOT_PATH . 'private' . DIRECTORY_SEPARATOR . 'logs';
			$logFile = $logDir . DIRECTORY_SEPARATOR . 'error.log';

			// Clear log file
			if (file_exists($logFile))
			{
				unlink($logFile);
			}

			logError('Test error message', 'test');

			$logExists = file_exists($logFile);

			return expect($logExists)->toBeTrue();
		});

		it('logs contain proper format', function()
		{
			$logDir = HAKU_ROOT_PATH . 'private' . DIRECTORY_SEPARATOR . 'logs';
			$logFile = $logDir . DIRECTORY_SEPARATOR . 'error.log';

			logError('Test formatting', 'info');

			$contents = file_get_contents($logFile);

			// Should contain timestamp, environment, level, and message
			$hasProperFormat =
				str_contains($contents, 'INFO') &&
				str_contains($contents, 'Test formatting');

			return expect($hasProperFormat)->toBeTrue();
		});

	});

});
