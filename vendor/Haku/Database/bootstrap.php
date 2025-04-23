<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Database\{
	ConnectionType,
};

/**
 *	Validates whether or not a database configuration is set.
 *	It does not validate whether or not the settings are correct.
 *
 *	@return bool
 */
function isConfigured(): bool
{
	$allVariablesDefined =
		defined('HAKU_DATABASE_TYPE') &&
		defined('HAKU_DATABASE_HOST') &&
		defined('HAKU_DATABASE_PORT') &&
		defined('HAKU_DATABASE_NAME') &&
		defined('HAKU_DATABASE_USER') &&
		defined('HAKU_DATABASE_PASS');

	if ($allVariablesDefined)
	{
		$needsSetValue = [
			HAKU_DATABASE_TYPE,
			HAKU_DATABASE_HOST,
			HAKU_DATABASE_NAME,
		];

		foreach ($needsSetValue as $value)
		{
			if (strlen($value) === 0)
			{
				return false;
			}
		}
	}

	return true;
}

/**
 *	Validates if configured database connectyion type is of a specific type.
 *
 *	@param \Haku\Database\ConnectionType $connectionType
 *
 *	@return bool
 */
function databaseType(ConnectionType $connectionType): bool
{
	if (!isConfigured())
	{
		return false;
	}

	return ConnectionType::from(HAKU_DATABASE_TYPE) === $connectionType;
}

/**
 *	Returns a SQL value from a mixed varoable.
 *
 *	@param mixed $value
 *
 *	@return mixed
 */
function sqlValueFrom(mixed $value): mixed
{
	if (is_numeric($value))
	{
		return $value;
	}
	else if (is_bool($value))
	{
		return $value ? 'TRUE' : 'FALSE';
	}
	else if (is_string($value))
	{
		return '"' . addslashes($value) . '"';
	}
	else if (is_array($value))
	{
		return '"' . implode(', ', $value) . '"';
	}

	return null;
}
