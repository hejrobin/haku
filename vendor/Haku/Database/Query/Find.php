<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Database\Query\{
	normalizeField,
	normalizeConditions,
	normalizeJoinStatements,
};

class Find
{

	protected const DefaultFetchLimit = 50;

	public static function all(
		string $tableName,
		array $fields,
		array $aggregateFields = [],

		array $where = [],
		array $joins = [],
		array $groupBy = [],
		array $orderBy = [],

		int $limit = Find::DefaultFetchLimit,
		int $offset = 0,

		?string $overrideFromTable = null,
		?bool $distinct = false,
	): array
	{
		// Normalize non-aggregate fields
		$normalizedFields = array_map(
			fn(string $field) => normalizeField($tableName, $field),
			$fields,
		);

		// Normalize aggregate fields if any
		if (count($aggregateFields) > 0)
		{
			foreach ($aggregateFields as $field => $aggregate)
			{
				$normalizedFields[] = sprintf('%2$s AS %1$s', $field, $aggregate);
			}
		}

		$querySegments = [
			'SELECT',
			$distinct ? 'DISTINCT' : '',
			implode(', ', $normalizedFields),
			'FROM',
			$overrideFromTable ?? $tableName,
		];

		if (count($joins) > 0)
		{
			array_push($querySegments, ...normalizeJoinStatements($tableName, $joins));
		}

		// Get normalized WHERE and HAVING
		$conditions = (object) normalizeConditions(
			$tableName,
			$where,
			$aggregateFields
		);

		// Combine parameters
		$parameters = [
			...$conditions->where->parameters,
			...$conditions->having->parameters
		];

		// Add WHERE
		if (count($conditions->where->clauses) > 0)
		{
			$querySegments = array_merge(
				$querySegments,
				['WHERE'],
				$conditions->where->clauses
			);
		}

		// Add GROUP BY
		if (count($groupBy) > 0)
		{
			$groupBy = array_map(
				fn(string $field) => normalizeField($tableName, $field),
				$groupBy,
			);

			$querySegments = array_merge(
				$querySegments,
				['GROUP BY'],
				[implode(', ', $groupBy)]
			);
		}

		// Add HAVING
		if (count($conditions->having->clauses) > 0)
		{
			$querySegments = array_merge(
				$querySegments,
				['HAVING'],
				$conditions->having->clauses
			);
		}

		// Add ORDER BY
		if (count($orderBy) > 0)
		{
			$orderBy = normalizeOrderByClauses(
				tableName: $tableName,
				aggregateFields: $aggregateFields,
				orderBy: $orderBy,
			);

			$querySegments = array_merge(
				$querySegments,
				[
					'ORDER BY',
					implode(', ', $orderBy),
				],
			);
		}

		// Add LIMIT
		if ($limit > 0)
		{
			$querySegments[] = sprintf('LIMIT %2$d, %1$d', $limit, $offset);
		}

		$query = preg_replace('/\s+/', ' ', implode(' ', $querySegments));

		return [
			$query,
			$parameters
		];
	}

	/**
	 *	Same as {@see Haku\Database\Query\Find::all}, but forces a LIMIT of 1.
	 */
	public static function one(
		string $tableName,
		array $fields,
		array $aggregateFields = [],

		array $joins = [],
		array $where = [],
		array $groupBy = [],
		array $orderBy = [],

		?string $overrideFromTable = null,
		?bool $distinct = false,
	): array
	{
		return static::all(
			distinct: $distinct,
			tableName: $tableName,
			overrideFromTable: $overrideFromTable,
			fields: $fields,
			aggregateFields: $aggregateFields,
			joins: $joins,
			where: $where,
			groupBy: $groupBy,
			orderBy: $orderBy,
			limit: 1,
		);
	}

	/**
	 *	Creates a count query by first building a sub query with all filtering applied and counts on that result.
	 */
	public static function count(
		string $tableName,
		string $countFieldName = 'id',
		array $aggregateFields = [],

		array $joins = [],
		array $where = [],
	): array
	{
		if (count($aggregateFields) > 0)
		{
			return self::complexCount(
				tableName: $tableName,
				countFieldName: $countFieldName,
				aggregateFields: $aggregateFields,
				joins: $joins,
				where: $where,
			);
		}
		else
		{
			return self::simpleCount(
				tableName: $tableName,
				countFieldName: $countFieldName,
				joins: $joins,
				where: $where,
			);
		}
	}

	/**
	 * Creates a "simple" count query without aggregates.
	 */
	protected static function simpleCount(
		string $tableName,
		string $countFieldName = 'id',

		array $joins = [],
		array $where = [],
	): array
	{
		// Get normalized WHERE and HAVING
		$conditions = (object) normalizeConditions(
			$tableName,
			$where,
		);

		$querySegments = [
			'SELECT',
			sprintf('COUNT(DISTINCT %s)', normalizeField($tableName, $countFieldName)),
			'FROM',
			$tableName,
		];

		if (count($joins) > 0)
		{
			array_push($querySegments, ...normalizeJoinStatements($tableName, $joins));
		}

		$parameters = $conditions->where->parameters;

		// Add WHERE
		if (count($conditions->where->clauses) > 0)
		{
			$querySegments = array_merge(
				$querySegments,
				['WHERE'],
				$conditions->where->clauses
			);
		}

		$query = implode(' ', $querySegments);

		return [
			$query,
			$parameters
		];
	}

	/**
	 *	Creates a "complex" count query that allows for aggregated filtering.
	 */
	protected static function complexCount(
		string $tableName,
		string $countFieldName = 'id',
		array $aggregateFields = [],

		array $joins = [],
		array $where = [],

	): array
	{
		// Get normalized WHERE and HAVING
		$conditions = (object) normalizeConditions(
			$tableName,
			$where,
			$aggregateFields
		);

		$normalizedFields = [];

		// Normalize aggregate fields if any
		if (count($aggregateFields) > 0)
		{
			foreach ($aggregateFields as $field => $aggregate)
			{
				$normalizedFields[] = sprintf('%2$s AS %1$s', $field, $aggregate);
			}
		}

		$querySegments = [
			'SELECT',
			implode(', ', $normalizedFields),
			'FROM',
			$tableName,
		];

		if (count($joins) > 0)
		{
			array_push($querySegments, ...normalizeJoinStatements($tableName, $joins));
		}

		$parameters = [
			...$conditions->where->parameters,
			...$conditions->having->parameters
		];

		// Add WHERE
		if (count($conditions->where->clauses) > 0)
		{
			$querySegments = array_merge(
				$querySegments,
				['WHERE'],
				$conditions->where->clauses
			);
		}

		// Add HAVING
		if (count($conditions->having->clauses) > 0)
		{
			$querySegments = array_merge(
				$querySegments,
				['HAVING'],
				$conditions->having->clauses
			);
		}

		$innerQuery = implode(' ', $querySegments);

		$query = sprintf(
			'SELECT COUNT(DISTINCT %s) AS count FROM %s AS o, (%s) AS i',
			$countFieldName,
			$tableName,
			$innerQuery
		);

		return [
			$query,
			$parameters
		];
	}

}
