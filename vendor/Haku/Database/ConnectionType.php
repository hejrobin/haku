<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 * @todo Add support for, Postgres and SQLite
 */
enum ConnectionType: string
{

	case MySQL = 'mysql';

	public function driverName(): string
	{
		return match ($this) {
			static::MySQL => 'mysql',
		};
	}

	public function connectionString(): string
	{
		return match ($this) {
			static::MySQL => 'host=%s;dbname=%s;port=%d;',
		};
	}
}
