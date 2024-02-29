<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\haku;

use Haku\Database\{
	Connection,
	ConnectionType,
};

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

function databaseType(ConnectionType $connectionType): bool
{
	if (!isConfigured())
	{
		return false;
	}

	return ConnectionType::from(HAKU_DATABASE_TYPE) === $connectionType;
}
