<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

enum ConnectionType: string {

	case MySQL = 'mysql';
	case PostgreSQL = 'postgresql';

	public function driverName(): string
	{
		return match ($this) {
			static::MySQL => 'mysql',
			static::PostgreSQL => 'pgsql',
		};
	}

	public function connectionString(): string
	{
		return match ($this) {
			static::MySQL => 'host=%s;dbname=%s;port=%d;',
			static::PostgreSQL => 'host=%s dbname=%s port=%d',
		};
	}
}
