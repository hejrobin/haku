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

/**
 *	Set up database connection.
 */
if (
	defined('HAKU_DATABASE_TYPE') &&
	defined('HAKU_DATABASE_HOST') &&
	defined('HAKU_DATABASE_PORT') &&
	defined('HAKU_DATABASE_NAME') &&
	defined('HAKU_DATABASE_USER') &&
	defined('HAKU_DATABASE_PASS')
) {
	haku()->initialize(
		'Haku\Database\Connection', 'db', null,
		[
			ConnectionType::from(HAKU_DATABASE_TYPE),
			HAKU_DATABASE_NAME,
			HAKU_DATABASE_HOST,
			HAKU_DATABASE_PORT,
		]
	);

	app('db')->connect(
		HAKU_DATABASE_USER,
		HAKU_DATABASE_PASS
	);
}

function databaseType(ConnectionType $connectionType): bool
{
	if (!defined('HAKU_DATABASE_TYPE'))
	{
		return false;
	}

	return ConnectionType::from(HAKU_DATABASE_TYPE) === $connectionType;
}
