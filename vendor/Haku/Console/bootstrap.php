<?php
declare(strict_types=1);

namespace Haku\Console;

use function Haku\resolvePath;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Parses arguments list from command line.
 *
 *	Supports the following patterns:
 *	- php haku command
 *	- php haku command context
 *	- php haku command context name
 *	- php haku command --flag value
 *	- php haku command context --flag value
 *	- php haku command context name --flag value
 *	- php haku command --flag="value"
 *	- php haku command -f
 *
 *	@example Basic command with context
 *	$args = resolveArguments(); // php haku env dev
 *	echo $args['command']; // 'env'
 *	echo $args['context']; // 'dev'
 *
 *	@example Command with context and name
 *	$args = resolveArguments(); // php haku make migration create_users
 *	echo $args['command']; // 'make'
 *	echo $args['context']; // 'migration'
 *	echo $args['migration']; // 'create_users'
 *
 *	@example Command with flags
 *	$args = resolveArguments(); // php haku make migration --from=User
 *	echo $args['arguments']['from']; // 'User'
 *
 *	@return array{command: ?string, context: ?string, arguments: array<string, mixed>, flags: array<string, bool>, showHelp: bool}
 */
function resolveArguments(): array
{
	global $argv;

	$args = [
		'command' => null,
		'context' => null,
		'arguments' => [],
		'flags' => [],
		'showHelp' => in_array('--help', $argv),
	];

	for ($n = 1; $n < count($argv); $n++)
	{
		$field = $argv[$n];
		$value = $argv[$n + 1] ?? null;

		// First argument is always the command
		if ($n === 1)
		{
			if ($field !== '--help')
			{
				$args['command'] = $field;
			}

			continue;
		}

		// Second argument (if not a flag) is the context
		if ($n === 2 && !str_starts_with($field, '-'))
		{
			$args['context'] = $field;

			continue;
		}

		// Third argument (if not a flag) is stored as a named parameter using context as key
		// e.g., php haku make migration create_users -> $args['migration'] = 'create_users'
		if ($n === 3 && !str_starts_with($field, '-') && is_string($args['context']))
		{
			$contextKey = (string) $args['context'];
			$args[ $contextKey] = $field;

			continue;
		}

		// Parse long flags: --flag, --flag value, --flag="value"
		if (str_starts_with($field, '--'))
		{
			$key = str_replace('--', '', $field);

			// Handle --flag="value" or --flag="val1,val2,val3"
			if (str_contains($field, '='))
			{
				[$tmpKey, $tmpValue] = explode('=', $field, 2);
				$key = str_replace('--', '', $tmpKey);

				// Remove quotes if present
				$tmpValue = trim($tmpValue, '"\'');

				// Split comma-separated values
				$value = str_contains($tmpValue, ',')
					? array_map(fn($v) => trim($v), explode(',', $tmpValue))
					: $tmpValue;

				$args['arguments'][$key] = $value;
			}
			// Handle --flag value (check if next arg is a value or another flag)
			else if ($value !== null && !str_starts_with($value, '-'))
			{
				$args['arguments'][$key] = $value;
				$n++; // Skip next arg since we consumed it
			}
			// Handle boolean flags: --flag (no value)
			else
			{
				$args['arguments'][$key] = true;
			}

			continue;
		}

		// Parse short flags: -f, -f value
		if (str_starts_with($field, '-'))
		{
			$key = str_replace('-', '', $field);

			// Check if next arg is a value (not a flag)
			if ($value !== null && !str_starts_with($value, '-'))
			{
				$args['flags'][$key] = $value;
				$n++; // Skip next arg since we consumed it
			}
			else
			{
				$args['flags'][$key] = true;
			}

			continue;
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
	$servicesPath = resolvePath('vendor', 'Haku', 'Console', 'Commands', 'Services');

	if (!is_dir($servicesPath))
	{
		return;
	}

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator(
			$servicesPath,
			 \RecursiveDirectoryIterator::SKIP_DOTS)
			,
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
