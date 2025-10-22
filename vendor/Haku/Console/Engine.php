<?php
declare(strict_types=1);

namespace Haku\Console;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Handles registered commands, output and invocation of Haku specific commands.
 */
class Engine extends Output
{

	public function __construct(
		protected array $commands = []
	) {
		parent::__construct();
	}

	public function registerCommand(Command $command): void
	{
		$this->commands[$command->name()] = $command;
	}

	/**
	 *	Outputs available commands if no command is given.
	 */
	protected function outputNilCommand(): void
	{
		$this->output('usage: haku [--help] <command> [<args>]');
		$this->break();

		$this->send('available commands:');
		$this->break();

		foreach ($this->commands as $trigger => $command)
		{
			$indentLength = calculateIndentLength([...array_keys($this->commands), $trigger]) + 4;

			$commandName = str_pad($command->name(), $indentLength);

			$this->send(sprintf(
				'  %s %s',
				$this->format($commandName, Ansi::Yellow),
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
				"'%s' is not a haku command. see 'haku --help'.",
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

		if (
			$arguments->showHelp &&
			is_callable([$command, 'help'])
		) {
			$command->help();

			return;
		}

		if ($arguments->showHelp)
		{
			$options = $command->options();
			$hasOptions = count($options) > 0;

			$output = sprintf("usage: haku %s", $this->format($command->name(), Ansi::Yellow));

			if ($command->requiresContext())
			{
				$output .= " <context>";
			}

			if ($hasOptions)
			{
				$parsed = [];

				foreach ($options as $option)
				{
					[$option, , $default] = explode('|', $option);

					if ($default)
					{
						$parsed[] = sprintf("[%s=%s]", $option, $default);
					}
					else
					{
						$parsed[] = sprintf("[%s]", $option);
					}
				}

				$output .= ' ' . implode(' ', $parsed);
			}

			$this->send($output);

			$this->break();
			$this->send($command->description());

			if ($hasOptions)
			{
				$this->break();
				$this->send('options:');

				$indent = max(array_map(function(string $option) {
					[$option] = explode('|', $option);

					return strlen($option);
				}, $options)) + 4;

				foreach($options as $option)
				{
					[$option, $description, $default] = explode('|', $option);

					if ($default)
					{
						$this->send(sprintf(
							'  %s %s (%s)',
							str_pad($option, $indent),
							$description,
							$default
						));
					}
					else {
						$this->send(sprintf(
							'  %s %s',
							str_pad($option, $indent),
							$description
						));
					}
				}
			}

			$this->break();
		}
		else
		{
			$didInvoke = $command->invoke();

			if (!$didInvoke)
			{
				$this->error(sprintf(
					'command failed: %s. see \'haku %s --help\'',
					$arguments->command,
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
