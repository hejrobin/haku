<?php
declare(strict_types=1);

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
	expectAll,
};

use function Haku\Console\{
	resolveArguments,
	calculateIndentLength,
};

spec('Console/bootstrap', function()
{

	describe('resolveArguments()', function()
	{

		it('parses basic command', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expect($result['command'])->toEqual('test');
		});

		it('parses command with help flag', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '--help'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['command'])->toEqual('test'),
				expect($result['showHelp'])->toBe(true),
			);
		});

		it('parses long flag with value', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '--only', 'mytest'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expect($result['arguments']['only'])->toEqual('mytest');
		});

		it('parses long flag with equals sign', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '--only=mytest'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expect($result['arguments']['only'][0])->toEqual('mytest');
		});

		it('parses long flag with comma-separated values', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '--only=test1,test2,test3'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect(count($result['arguments']['only']))->toBe(3),
				expect($result['arguments']['only'][0])->toEqual('test1'),
				expect($result['arguments']['only'][1])->toEqual('test2'),
				expect($result['arguments']['only'][2])->toEqual('test3'),
			);
		});

		it('parses short flag', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '-f'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expect($result['flags']['f'])->toBe(true);
		});

		it('parses multiple short flags', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '-f', '-o'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['flags']['f'])->toBe(true),
				expect($result['flags']['o'])->toBe(true),
			);
		});

		it('handles no command', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expect($result['command'])->toBe(null);
		});

		it('handles --help without command', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', '--help'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['command'])->toBe(null),
				expect($result['showHelp'])->toBe(true),
			);
		});

		it('parses mixed arguments and flags', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '--only', 'mytest', '-f', '--verbose'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['command'])->toEqual('test'),
				expect($result['arguments']['only'])->toEqual('mytest'),
				expect($result['flags']['f'])->toBe(true),
			);
		});

	});

	describe('calculateIndentLength()', function()
	{

		it('calculates max length of strings', function()
		{
			$items = ['short', 'medium', 'verylongstring'];

			return expect(calculateIndentLength($items))->toBe(14);
		});

		it('handles single item', function()
		{
			$items = ['test'];

			return expect(calculateIndentLength($items))->toBe(4);
		});

		it('calculates with equal length strings', function()
		{
			$items = ['test', 'code', 'help'];

			return expect(calculateIndentLength($items))->toBe(4);
		});

	});

});
