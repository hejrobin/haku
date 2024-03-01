<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Database\Query\{
	normalizeField,
	normalizeWhereClauses,
	normaizeOrderByClauses,
};

class Find
{

	protected const DefaultFetchLimit = 50;

	/**
	 *	Returns SQL query and associated parameters for a "fetch many" query.
	 */
	public static function all(
		string $tableName,
		array $tableFields,
		array $aggregateFields = [],
		array $where = [],
		array $orderBy = [],
		int $limit = Find::DefaultFetchLimit,
		int $offset = 0,
	): array
	{
		$normalizedFields = array_map(
			fn(string $field) => normalizeField($tableName, $field),
			$tableFields,
		);

		if (count($aggregateFields) > 0)
		{
			foreach ($aggregateFields as $field => $aggregate)
			{
				$normalizedFields[] = sprintf('%2$s AS %1$s', $field, $aggregate);
			}
		}

		$queryPattern = 'SELECT %2$s FROM %1$s';

		if (count($where) > 0)
		{
			$queryPattern .= ' WHERE ';
		}

		$queryPattern .= '%3$s';

		if (count($orderBy) > 0)
		{
			$queryPattern .= ' ORDER BY ';
		}

		$queryPattern .= '%4$s';
		$queryPattern .= ' LIMIT %6$d, %5$d';

		[$conditions, $parameters] = normalizeWhereClauses($tableName, $where);
		$orderBy = normalizeOrderByClauses($tableName, $orderBy);

		$query = sprintf(
			trim($queryPattern),
			$tableName,
			implode(', ', $normalizedFields),
			implode(' ', $conditions),
			implode(', ', $orderBy),
			$limit,
			$offset
		);

		return [$query, $parameters];
	}

	/**
	 *	Same as {@see Haku\Database\Query\Find::all}, but forces a LIMIT of 1.
	 */
	public static function one(
		string $tableName,
		array $tableFields,
		array $aggregateFields = [],
		array $where = [],
		array $orderBy = [],
	): array
	{
		return static::all(
			tableName: $tableName,
			tableFields: $tableFields,
			aggregateFields: $aggregateFields,
			where: $where,
			orderBy: $orderBy,
			limit: 1
		);
	}

	/**
	 *	Returns a count query.
	 */
	public static function count(
		string $tableName,
		string $countFieldName = '*',
		array $where = []
	): array
	{
		$queryPattern = 'SELECT COUNT(%2$s) FROM %1$s';

		if (count($where) > 0)
		{
			$queryPattern .= ' WHERE ';
		}

		$queryPattern .= '%3$s';

		[$conditions, $parameters] = normalizeWhereClauses($tableName, $where);

		$query = sprintf(
			trim($queryPattern),
			$tableName,
			normalizeField($tableName, $countFieldName),
			implode(' ', $conditions),
		);

		return [$query, $parameters];
	}

}
