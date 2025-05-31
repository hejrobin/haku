<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Generic\Query\{
	Params,
	Filter,
};

use Haku\Database\Query\OrWhere;

class Conditions
{

	public static function fromQueryString(
		?string $unresolved = '',
		string $searchParameterName = 'search',
		array $searchableFields = [],
	): array
	{
		$params = new Params($unresolved);

		if (!$params->has('filters'))
		{
			return [];
		}

		$filter = Filter::from(
			json_decode($params->get('filters'), true)
		);

		$where = static::from($filter);

		if (count($searchableFields) > 0 && $params->has($searchParameterName))
		{
			foreach ($searchableFields as $searchable)
			{
				$where[] = OrWhere::like(
					$searchable,
					$params->get($searchParameterName)
				);
			}
		}

		return $where;
	}

	public static function from(Filter $filter): array
	{
		$where = [];

		foreach ($filter->getFilters() as $property)
		{
			if ($property->operator->value === 'custom')
			{
				continue;
			}

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
