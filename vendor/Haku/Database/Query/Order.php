<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Generic\Query\Params;

use function Haku\Database\normalizeField;

enum Direction: string
{

	case Asc = 'ASC';
	case Desc = 'DESC';

}

class Order
{

	public static function fromQueryString(
		?string $unresolved = '',
		string $queryParameterName = 'orderBy',
	): array
	{
		$orders = [];
		$params = new Params($unresolved);

		if (
			$params->has($queryParameterName) === false ||
			strlen($params->get($queryParameterName)) === 0
		) {
			return [];
		}

		$allowedDirections = ['ASC', 'DESC'];
		$segments = explode(',', $params->get($queryParameterName));

		foreach ($segments as $segment)
		{
			[$field, $direction] = explode(':', $segment);
			$direction = mb_strtoupper($direction);

			if (in_array($direction, $allowedDirections))
			{
				$orders[] = self::by(
					$field,
					Direction::from($direction),
				);
			}
		}

		return $orders;
	}

	public static function by(
		string $field,
		Direction $direction = Direction::Asc,
	): array
	{
		return [$field, $direction->value];
	}

}
