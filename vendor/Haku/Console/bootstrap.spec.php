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

spec('Console', function()
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

			return expect($result['arguments']['only'])->toEqual('mytest');
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

		it('parses command with context', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'env', 'dev'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['command'])->toEqual('env'),
				expect($result['context'])->toEqual('dev'),
			);
		});

		it('parses command with context and flags', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'env', 'dev', '--regenerate'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['command'])->toEqual('env'),
				expect($result['context'])->toEqual('dev'),
				expect($result['arguments']['regenerate'])->toBe(true),
			);
		});

		it('parses make command with generator and name', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'make', 'migration', 'create_users_table', '--from', 'User'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['command'])->toEqual('make'),
				expect($result['context'])->toEqual('migration'),
				expect($result['migration'])->toEqual('create_users_table'),
				expect($result['arguments']['from'])->toEqual('User'),
			);
		});

		it('parses context when followed by equals-style flag', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'make', 'migration', '--from=MyModel'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['command'])->toEqual('make'),
				expect($result['context'])->toEqual('migration'),
				expect($result['arguments']['from'])->toEqual('MyModel'),
			);
		});

		it('parses short flag with value', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '-f', 'value'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expect($result['flags']['f'])->toEqual('value');
		});

		it('parses boolean long flag without value', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'env', 'dev', '--regenerate', '--verbose'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['arguments']['regenerate'])->toBe(true),
				expect($result['arguments']['verbose'])->toBe(true),
			);
		});

		it('stores third positional arg using context as key', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'make', 'route', 'UserRoute'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['command'])->toEqual('make'),
				expect($result['context'])->toEqual('route'),
				expect($result['route'])->toEqual('UserRoute'),
			);
		});

		it('handles third arg with context and flags', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'make', 'migration', 'create_accounts', '--from=Account'];
			$result = resolveArguments();

			$argv = $originalArgv;

			return expectAll(
				expect($result['command'])->toEqual('make'),
				expect($result['context'])->toEqual('migration'),
				expect($result['migration'])->toEqual('create_accounts'),
				expect($result['arguments']['from'])->toEqual('Account'),
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
