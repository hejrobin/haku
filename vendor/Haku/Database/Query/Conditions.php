<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Generic\Query\{
	Params,
	Filter,
};

class Conditions
{

	public static function fromQueryString(?string $unresolved = ''): array
	{
		$params = new Params();

		if (!$params->has('filters'))
		{
			return [];
		}

		$filter = Filter::from($params->get('filters'));

		return static::from($filter);
	}

	public static function from(Filter $filter): array
	{
		$where = [];

		foreach ($filter->getFilters() as $property)
		{
			foreach ($property->values as $value)
			{
				$where[] = call_user_func_array(
					"Haku\Database\Query\Where::{$property->operator->value}",
					[$property->name, $value]
				);
			}
		}

		return $where;
	}

}
