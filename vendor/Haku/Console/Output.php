<?php
declare(strict_types=1);

namespace Haku\Console;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/* @note Define STDIN and STDOUT if they are not set up */
defined('STDIN') || define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') || define('STDOUT', fopen('php://stdout', 'w'));

/**
 *	Handles output to the terminal
 */
class Output
{

	protected bool $useAnsi = true;

	public function __construct()
	{
		global $argv;

		if (
			is_array($argv) &&
			in_array('--no-ansi', $argv)
		) {
			$this->useAnsi = false;
		}
	}

	/**
	 *	Inserts line break.
	 */
	public function ln(
		int $numLines = 1
	): string
	{
		return str_repeat("\n", $numLines);
	}

	/**
	 *	Inserts a tab.
	 */
	public function indent(
		int $numIndents = 1
	): string
	{
		return str_repeat('  ', $numIndents);
	}

	/**
	 *	Formats an ANSI tag.
	 */
	protected function formatTag(
		Ansi $tag
	): string
	{
		if ($this->useAnsi)
		{
			return implode([ Ansi::openTag(), $tag->value, Ansi::closeTag() ]);
		}

		return '';
	}

	/**
	 *	Formats a string with selected ANSI formatting options.
	 */
	public function format(
		string | int | float $value,
		Ansi ...$formats
	): string
	{
		if ($this->useAnsi === false)
		{
			return $value;
		}

		$output = [];

		foreach ($formats as $format)
		{
			$output[] = $this->formatTag($format);
		}

		array_push(
			$output,
			$value,
			$this->formatTag(Ansi::Off)
		);

		return implode($output);
	}

	/**
	 *	Sends output to console.
	 */
	public function send(
		string ...$strings
	): void
	{
		$strings[] = "\n";

		fputs(STDOUT, implode($strings));
	}

	/**
	 *	Sends a single linbreak to output.
	 */
	public function break(
		int $numLines = 1
	): void
	{
		fputs(STDOUT, $this->ln($numLines));
	}

	/**
	 *	Outputs message with the format "[context]: message", the context will be formatted with whatever Ansi enum you're sending.
	 */
	public function output(
		string $message,
		string $context = 'haku',
		Ansi $format = Ansi::Cyan
	): void
	{
		$this->send(sprintf(
			'[%s]: %s',
			$this->format($context, $format),
			$message,
		));
	}

	/**
	 *	Outputs a informational message.
	 */
	public function info(
		string $message,
	): void
	{
		$this->output($message, 'info', Ansi::Blue);
	}

	/**
	 *	Outputs a warning message.
	 */
	public function warn(
		string $message
	): void
	{
		$this->output($message, 'warn', Ansi::Yellow);
	}

	/**
	 *	Outputs an error message.
	 */
	public function error(
		string $message
	): void
	{
		$this->output($message, 'error', Ansi::Red);
	}

	/**
	 *	Outputs a success message.
	 */
	public function success(
		string $message
	): void
	{
		$this->output($message, 'ok', Ansi::Green);
	}

}
