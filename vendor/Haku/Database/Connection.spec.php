<?php
declare(strict_types=1);

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
};

use function Haku\haku;

use Haku\Database\Connection;
use Haku\Database\ConnectionType;

spec('Database/Connection', function()
{

	describe('Database connectivity', function()
	{

		it('can connect to the database', function()
		{
			$db = haku('db');

			return expect($db)->toBeInstanceOf(Connection::class);
		});

		it('can execute a simple query', function()
		{
			$db = haku('db');
			$result = $db->query('SELECT 1 as test');

			return expect($result)->toBeTypeOf('object');
		});

	});

}, tags: ['database']);
