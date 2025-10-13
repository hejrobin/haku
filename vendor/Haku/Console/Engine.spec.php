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

use Haku\Console\Engine;
use Haku\Console\Command;

// Test commands for Engine testing
class MockSuccessCommand extends Command
{
	public function name(): string
	{
		return 'mock-success';
	}

	public function description(): string
	{
		return 'A mock command that succeeds';
	}

	public function invoke(): bool
	{
		return true;
	}
}

class MockFailCommand extends Command
{
	public function name(): string
	{
		return 'mock-fail';
	}

	public function description(): string
	{
		return 'A mock command that fails';
	}

	public function invoke(): bool
	{
		return false;
	}
}

class MockCommandWithOptions extends Command
{
	public function name(): string
	{
		return 'mock-options';
	}

	public function description(): string
	{
		return 'A mock command with options';
	}

	public function options(): array
	{
		return [
			'--flag|Enable a feature|',
			'--option|Set a value|default',
		];
	}

	public function invoke(): bool
	{
		return true;
	}
}

spec('Console/Engine', function()
{

	describe('Engine initialization', function()
	{

		it('creates engine instance', function()
		{
			$engine = new Engine();

			return expect($engine instanceof Engine)->toBe(true);
		});

		it('creates engine with initial commands', function()
		{
			$command = new MockSuccessCommand();
			$engine = new Engine([$command->name() => $command]);

			return expect($engine instanceof Engine)->toBe(true);
		});

	});

	describe('Command registration', function()
	{

		it('registers a command', function()
		{
			$engine = new Engine();
			$command = new MockSuccessCommand();

			$engine->registerCommand($command);

			// We can't directly check private properties, but we can verify no exception was thrown
			return expect(true)->toBe(true);
		});

		it('registers multiple commands', function()
		{
			$engine = new Engine();
			$command1 = new MockSuccessCommand();
			$command2 = new MockFailCommand();

			$engine->registerCommand($command1);
			$engine->registerCommand($command2);

			return expect(true)->toBe(true);
		});

	});

	describe('Engine run with no command', function()
	{

		it('handles empty command list without error', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku'];
			$engine = new Engine();

			// Engine::run() outputs to STDOUT which we can't easily capture
			// Just verify it runs without throwing exceptions
			try {
				$engine->run();
				$success = true;
			} catch (\Throwable $e) {
				$success = false;
			}

			$argv = $originalArgv;

			return expect($success)->toBe(true);
		});

		it('runs when commands are registered', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku'];
			$engine = new Engine();
			$engine->registerCommand(new MockSuccessCommand());
			$engine->registerCommand(new MockFailCommand());

			// Verify it runs without throwing exceptions
			try {
				$engine->run();
				$success = true;
			} catch (\Throwable $e) {
				$success = false;
			}

			$argv = $originalArgv;

			return expect($success)->toBe(true);
		});

	});

	describe('Engine run with invalid command', function()
	{

		it('runs without error for non-existent command', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'nonexistent'];
			$engine = new Engine();

			// Verify it runs without throwing exceptions
			try {
				$engine->run();
				$success = true;
			} catch (\Throwable $e) {
				$success = false;
			}

			$argv = $originalArgv;

			return expect($success)->toBe(true);
		});

	});

	describe('Engine run with valid command', function()
	{

		it('executes successful command', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'mock-success'];
			$engine = new Engine();
			$engine->registerCommand(new MockSuccessCommand());

			// Verify successful command runs without exceptions
			try {
				$engine->run();
				$success = true;
			} catch (\Throwable $e) {
				$success = false;
			}

			$argv = $originalArgv;

			return expect($success)->toBe(true);
		});

		it('handles failed command', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'mock-fail'];
			$engine = new Engine();
			$engine->registerCommand(new MockFailCommand());

			// Verify failed command runs without exceptions (it outputs error but doesn't throw)
			try {
				$engine->run();
				$success = true;
			} catch (\Throwable $e) {
				$success = false;
			}

			$argv = $originalArgv;

			return expect($success)->toBe(true);
		});

	});

	describe('Engine run with --help flag', function()
	{

		it('shows help for command without error', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'mock-success', '--help'];
			$engine = new Engine();
			$engine->registerCommand(new MockSuccessCommand());

			// Verify help runs without exceptions
			try {
				$engine->run();
				$success = true;
			} catch (\Throwable $e) {
				$success = false;
			}

			$argv = $originalArgv;

			return expect($success)->toBe(true);
		});

		it('shows options when command has options', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'mock-options', '--help'];
			$engine = new Engine();
			$engine->registerCommand(new MockCommandWithOptions());

			// Verify help with options runs without exceptions
			try {
				$engine->run();
				$success = true;
			} catch (\Throwable $e) {
				$success = false;
			}

			$argv = $originalArgv;

			return expect($success)->toBe(true);
		});

		it('shows usage without args when no options', function()
		{
			global $argv;
			$originalArgv = $argv;

			$argv = ['haku', 'mock-success', '--help'];
			$engine = new Engine();
			$engine->registerCommand(new MockSuccessCommand());

			// Verify help runs without exceptions
			try {
				$engine->run();
				$success = true;
			} catch (\Throwable $e) {
				$success = false;
			}

			$argv = $originalArgv;

			return expect($success)->toBe(true);
		});

	});

	describe('Engine extends Output', function()
	{

		it('inherits Output methods', function()
		{
			$engine = new Engine();

			return expectAll(
				expect(method_exists($engine, 'format'))->toBe(true),
				expect(method_exists($engine, 'send'))->toBe(true),
				expect(method_exists($engine, 'output'))->toBe(true),
			);
		});

	});

});
