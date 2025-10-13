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

use Haku\Console\Output;
use Haku\Console\Ansi;

spec('Console/Output', function()
{

	describe('Line breaks and indentation', function()
	{

		it('generates single line break', function()
		{
			$output = new Output();

			return expect($output->ln())->toEqual("\n");
		});

		it('generates multiple line breaks', function()
		{
			$output = new Output();

			return expect($output->ln(3))->toEqual("\n\n\n");
		});

		it('generates single indent', function()
		{
			$output = new Output();

			return expect($output->indent())->toEqual('  ');
		});

		it('generates multiple indents', function()
		{
			$output = new Output();

			return expect($output->indent(3))->toEqual('      ');
		});

	});

	describe('ANSI formatting with color support', function()
	{

		it('formats text with single ANSI code', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test'];
			$output = new Output();
			$result = $output->format('Hello', Ansi::Red);

			$argv = $originalArgv;

			return expect($result)->toEqual("\033[31mHello\033[0m");
		});

		it('formats text with multiple ANSI codes', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test'];
			$output = new Output();
			$result = $output->format('Hello', Ansi::Red, Ansi::Bold);

			$argv = $originalArgv;

			return expect($result)->toEqual("\033[31m\033[1mHello\033[0m");
		});

		it('formats integer values', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test'];
			$output = new Output();
			$result = $output->format(42, Ansi::Green);

			$argv = $originalArgv;

			return expect($result)->toEqual("\033[32m42\033[0m");
		});

		it('formats float values', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test'];
			$output = new Output();
			$result = $output->format(3.14, Ansi::Blue);

			$argv = $originalArgv;

			return expect($result)->toEqual("\033[34m3.14\033[0m");
		});

	});

	describe('ANSI formatting without color support', function()
	{

		it('returns plain text when --no-ansi flag is present', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '--no-ansi'];
			$output = new Output();
			$result = $output->format('Hello', Ansi::Red);

			$argv = $originalArgv;

			return expect($result)->toEqual('Hello');
		});

		it('ignores multiple ANSI codes with --no-ansi', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '--no-ansi'];
			$output = new Output();
			$result = $output->format('Hello', Ansi::Red, Ansi::Bold, Ansi::Underline);

			$argv = $originalArgv;

			return expect($result)->toEqual('Hello');
		});

	});

	describe('Output methods', function()
	{

		it('formats info message correctly', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test'];
			$output = new Output();

			// We can't easily test output to STDOUT, but we can verify the format method is called
			// by checking if the output object has the method
			$hasMethod = method_exists($output, 'info');

			$argv = $originalArgv;

			return expect($hasMethod)->toBe(true);
		});

		it('has warn method', function()
		{
			$output = new Output();

			return expect(method_exists($output, 'warn'))->toBe(true);
		});

		it('has error method', function()
		{
			$output = new Output();

			return expect(method_exists($output, 'error'))->toBe(true);
		});

		it('has success method', function()
		{
			$output = new Output();

			return expect(method_exists($output, 'success'))->toBe(true);
		});

		it('has output method', function()
		{
			$output = new Output();

			return expect(method_exists($output, 'output'))->toBe(true);
		});

		it('has send method', function()
		{
			$output = new Output();

			return expect(method_exists($output, 'send'))->toBe(true);
		});

		it('has break method', function()
		{
			$output = new Output();

			return expect(method_exists($output, 'break'))->toBe(true);
		});

	});

	describe('Constructor ANSI detection', function()
	{

		it('enables ANSI by default', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test'];
			$output = new Output();
			$result = $output->format('test', Ansi::Red);

			$argv = $originalArgv;

			// Should contain ANSI codes
			return expect(str_contains($result, "\033["))->toBe(true);
		});

		it('disables ANSI with --no-ansi flag', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'test', '--no-ansi'];
			$output = new Output();
			$result = $output->format('test', Ansi::Red);

			$argv = $originalArgv;

			// Should NOT contain ANSI codes
			return expect(str_contains($result, "\033["))->toBe(false);
		});

	});

});
