<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Database\normalizeField;

enum Direction: string
{

	case Asc = 'ASC';
	case Desc = 'DESC';

}

class Order
{

	static function by(
		string $field,
		Direction $direction = Direction::Asc,
		bool $isAggregate = false,
	): array
	{
		return [$field, $direction->value, $isAggregate];
	}

}
