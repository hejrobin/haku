<?php
declare(strict_types=1);

namespace Haku\Console;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Parses arguments list from command line.
 */
function resolveArguments(
	?string $triggerNextAsArgument = null,
	?array $nextAsArgumentTriggers = [],
	?string $triggerFieldName = null,
): array
{
	global $argv;

	$args = [
		'command' => null,
		'arguments' => [],
		'flags' => [],
		'showHelp' => in_array('--help', $argv),
	];

	for ($n = 1; $n < count($argv); $n++)
	{
		$field = $argv[$n];
		$value = !empty($argv[$n + 1]) ? $argv[$n + 1] : null;

		// First argument is always initial command
		if ($n === 1)
		{
			if ($field !== '--help')
			{
				$args['command'] = $field;
			}

			continue;
		}
		// Parse long flags --hello, --hello world and --hello="hello world"
		else if (str_starts_with($field, '--'))
		{
			$key = str_replace('--', '', $field);

			if (\str_contains($field, '='))
			{
				[$tmpKey, $tmpValue] = explode('=', $field);

				$key = str_replace('--', '', $tmpKey);

				$value = array_map(
					fn($v) => trim($v, ' '),
					explode(',', $tmpValue)
				);
			}
			// Check if the next value is another flag (starts with --)
			// If so, this is a boolean flag without a value
			else if ($value !== null && str_starts_with($value, '--'))
			{
				$value = null;
				$n--; // Don't skip the next arg since it's another flag
			}

			$args['arguments'][$key] = $value;
			$n++; // Skip next arg
		}
		// Parse short flags such as -o, -f
		else if (\str_starts_with($field, '-'))
		{
			$args['flags'][str_replace('-', '', $field)] = true;
		}
		else
		{
			$fieldValue = null;

			// Handle cases where we want to capture the argument after a specfic command.
			// For example: php haku make <generator> to capture whatever "generator" is.
			if (
				$triggerNextAsArgument !== null &&
				$args['command'] === $triggerNextAsArgument
			) {
				if (in_array($field, $nextAsArgumentTriggers))
				{
					if (!array_key_exists($n + 1, $argv))
					{
						continue;
					}

					$next = $argv[$n + 1];

					if (!\str_starts_with($next, '-'))
					{
						$fieldValue = $next;

						if ($triggerFieldName !== null)
						{
							$args[$triggerFieldName] = $field;
						}

						$n++; // Skip next arg
					}
				}
			}

			$args[$field] = $fieldValue;
		}
	}

	return $args;
}

function calculateIndentLength(array $items): int
{
	return max(array_map('strlen', $items));
}

/**
 *	Recursively loads all service files from Commands/Services directory.
 *
 *	@return void
 */
function loadConsoleServices(): void
{
	$servicesPath = HAKU_ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'Haku' . DIRECTORY_SEPARATOR . 'Console' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'Services';

	if (!is_dir($servicesPath))
	{
		return;
	}

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator($servicesPath, \RecursiveDirectoryIterator::SKIP_DOTS),
		\RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ($iterator as $file)
	{
		if ($file->isFile() && $file->getExtension() === 'php')
		{
			require_once $file->getPathname();
		}
	}
}
