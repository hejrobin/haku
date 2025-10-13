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

use Haku\Console\Command;
use Haku\Console\Output;

// Create a concrete test command for testing
class TestCommand extends Command
{
	public function description(): string
	{
		return 'A test command for unit testing';
	}

	public function invoke(): bool
	{
		return true;
	}

	public function getArguments(): object
	{
		return $this->arguments;
	}

	public function getOutput(): Output
	{
		return $this->output;
	}
}

// Test command with custom name
class CustomNameCommand extends Command
{
	public function name(): string
	{
		return 'custom-name';
	}

	public function description(): string
	{
		return 'Command with custom name';
	}

	public function invoke(): bool
	{
		return false;
	}
}

// Test command with options
class CommandWithOptions extends Command
{
	public function description(): string
	{
		return 'Command with options';
	}

	public function options(): array
	{
		return [
			'--flag|Enable a flag|default',
			'--option|Set an option|',
		];
	}

	public function invoke(): bool
	{
		return true;
	}
}

spec('Console/Command', function()
{

	describe('Command initialization', function()
	{

		it('initializes with Output instance', function()
		{
			$command = new TestCommand();
			$output = $command->getOutput();

			return expect($output instanceof Output)->toBe(true);
		});

		it('initializes with arguments object', function()
		{
			$command = new TestCommand();
			$args = $command->getArguments();

			return expect(is_object($args))->toBe(true);
		});

	});

	describe('Command name resolution', function()
	{

		it('generates name from class name by default', function()
		{
			$command = new TestCommand();

			return expect($command->name())->toEqual('testcommand');
		});

		it('allows custom name override', function()
		{
			$command = new CustomNameCommand();

			return expect($command->name())->toEqual('custom-name');
		});

		it('converts name to lowercase', function()
		{
			$command = new TestCommand();
			$name = $command->name();

			return expect(ctype_lower($name))->toBe(true);
		});

	});

	describe('Command description', function()
	{

		it('returns description string', function()
		{
			$command = new TestCommand();

			return expect($command->description())->toEqual('A test command for unit testing');
		});

		it('requires description implementation', function()
		{
			$command = new TestCommand();
			$hasDescription = method_exists($command, 'description');

			return expect($hasDescription)->toBe(true);
		});

	});

	describe('Command options', function()
	{

		it('returns empty array by default', function()
		{
			$command = new TestCommand();
			$options = $command->options();

			return expectAll(
				expect(is_array($options))->toBe(true),
				expect(count($options))->toBe(0),
			);
		});

		it('can return custom options', function()
		{
			$command = new CommandWithOptions();
			$options = $command->options();

			return expectAll(
				expect(is_array($options))->toBe(true),
				expect(count($options))->toBe(2),
			);
		});

	});

	describe('Command invocation', function()
	{

		it('can invoke and return success', function()
		{
			$command = new TestCommand();
			$result = $command->invoke();

			return expect($result)->toBe(true);
		});

		it('can invoke and return failure', function()
		{
			$command = new CustomNameCommand();
			$result = $command->invoke();

			return expect($result)->toBe(false);
		});

		it('requires invoke implementation', function()
		{
			$command = new TestCommand();
			$hasInvoke = method_exists($command, 'invoke');

			return expect($hasInvoke)->toBe(true);
		});

	});

});
