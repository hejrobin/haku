<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Database\Query\{
	normalizeField,
	normalizeWhereClauses,
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
		array $whereClauses = [],
		int $limit = Find::DefaultFetchLimit,
		int $offset = 0,
	): array
	{
		$normalizedFields = array_map(
			fn(string $field) => normalizeField($tableName, $field),
			$tableFields,
		);

		$queryPattern = 'SELECT %2$s FROM %1$s';

		if (count($whereClauses) > 0)
		{
			$queryPattern .= ' WHERE ';
		}

		$queryPattern .= '%3$s LIMIT %5$d, %4$d';

		[$conditions, $parameters] = normalizeWhereClauses($tableName, $whereClauses);

		$query = sprintf(
			trim($queryPattern),
			$tableName,
			implode(', ', $normalizedFields),
			implode(' ', $conditions),
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
		array $whereClauses = []
	): array
	{
		return static::all($tableName, $tableFields, $whereClauses, 1);
	}

	/**
	 *	Returns a count query.
	 */
	public static function count(
		string $tableName,
		string $countFieldName = '*',
		array $whereClauses = []
	): array
	{
		$queryPattern = 'SELECT COUNT(%2$s) FROM %1$s';

		if (count($whereClauses) > 0)
		{
			$queryPattern .= ' WHERE ';
		}

		$queryPattern .= '%3$s';

		[$conditions, $parameters] = normalizeWhereClauses($tableName, $whereClauses);

		$query = sprintf(
			trim($queryPattern),
			$tableName,
			normalizeField($tableName, $countFieldName),
			implode(' ', $conditions),
		);

		return [$query, $parameters];
	}

}
