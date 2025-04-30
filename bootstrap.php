<?php
declare(strict_types=1);

namespace Haku;

use Haku\Database\ConnectionType;

use function Haku\Database\isConfigured;

/**
 *	Set up database connection.
 */
if (isConfigured()) {
	$db = haku()->initialize(
		'Haku\Database\Connection', 'db', null,
		[
			ConnectionType::from(HAKU_DATABASE_TYPE),
			HAKU_DATABASE_NAME,
			HAKU_DATABASE_HOST,
			HAKU_DATABASE_PORT,
		]
	);

	$db->login(
		HAKU_DATABASE_USER,
		HAKU_DATABASE_PASS
	);
}
