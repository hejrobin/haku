<?php
declare(strict_types=1);

namespace Haku\Console;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Abstract class used to create Haku terminal commands.
 */
abstract class Command
{

	protected Output $output;

	protected object $arguments;

	public function __construct()
	{
		$this->output = new Output();
		$this->resolveArguments();
	}

	/**
	 *	@overridable
	 *
	 *	Return object of parsed command arguments.
	 */
	protected function resolveArguments(): void
	{
		$this->arguments = (object) resolveArguments();
	}

	/**
	 *	@overridable
	 *
	 *	Returns a qualified command name, defaults to class name in lowercase
	 */
	public function name(): string
	{
		return mb_strtolower(basename(str_replace('\\', '/', static::class)));
	}

	/**
	 *	Must return command description.
	 */
	abstract public function description(): string;

	/**
	 *	@overridable
	 *
	 *	Should return an array of strings of available command options.
	 */
	public function options(): array
	{
		return [];
	}

	/**
	 *	@overridable
	 *
	 *	Returns whether or not command has a context, for example "haku make <context>".
	 */
	public function requiresContext(): bool
	{
		return false;
	}

	/**
	 *	Attempt to execute command, return true on success and false on failure.
	 */
	abstract public function invoke(): bool;

}
