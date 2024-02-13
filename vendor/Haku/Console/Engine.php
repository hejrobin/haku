<?php
declare(strict_types=1);

namespace Haku\Console;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Engine extends Output
{

	public function __construct(
		protected array $commands = []
	) {
		parent::__construct();
	}

	public function registerCommand(Command $command, bool $triggerNextAsArgument = false): void
	{
		$this->commands[$command->name()] = $command;
	}

	/**
	 *	Outputs available commands if no command is given.
	 */
	protected function outputNilCommand(): void
	{
		$this->output('available commands:');
		$this->break();

		foreach ($this->commands as $trigger => $command)
		{
			$indentLength = calculateIndentLength(array_keys($this->commands), $trigger) + 2;

			$commandName = str_pad($command->name(), $indentLength);

			$this->send(sprintf(
				'%s — %s',
				$this->format($commandName, Ansi::Cyan),
				$command->description(),
			));
		}

	}

	/**
	 *	Outputs error message when command defined, but not found
	 */
	protected function outputCommandNotFound(object $arguments): void
	{
		$this->output(
			sprintf(
				'no such command: %s',
				$arguments->command
			),
			'haku',
			Ansi::Red
		);
	}

	/**
	 *	Executes existing command.
	 */
	protected function outputCommand(object $arguments): void
	{
		$command = $this->commands[$arguments->command];

		if ($arguments->showHelp)
		{
			$options = $command->options();
			$hasOptions = count($options) > 0;

			$this->send(sprintf(
				'%s — %s',
				$this->format($command->name(), Ansi::Cyan),
				$command->description(),
			));

			$this->send(sprintf(
				$hasOptions ? 'usage: %s <args>' : 'usage: %s',
				$this->format("haku {$command->name()}", Ansi::Cyan)
			));

			if ($hasOptions)
			{
				$this->break();
				$this->send('options:');

				foreach($options as $option)
				{
					[$option, $description, $default] = explode('|', $option);

					if ($default)
					{
						$this->send(sprintf('  %s — %s (%s)', $option, $description, $default));
					}
					else {
						$this->send(sprintf('  %s — %s', $option, $description));
					}
				}
			}
		}
		else
		{
			$didInvoke = $command->invoke();

			if (!$didInvoke)
			{
				$this->error(sprintf(
					'command failed: %s',
					$arguments->command
				));
			}
		}
	}

	public function run(): void
	{
		$arguments = (object) resolveArguments();

		$hasCommand = !empty($arguments->command);
		$commandExists = array_key_exists($arguments->command, $this->commands);

		if (!$hasCommand)
		{
			$this->outputNilCommand();
		}
		else if (!$commandExists)
		{
			$this->outputCommandNotFound($arguments);
		}
		else
		{
			$this->outputCommand($arguments);
		}
	}

}
