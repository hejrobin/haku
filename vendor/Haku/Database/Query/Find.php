<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Database\Query\{
	normalizeField,
	normalizeConditions,
	normaizeOrderByClauses,
};

class Find
{

	protected const DefaultFetchLimit = 50;

	public static function all(
		string $tableName,
		array $fields,
		array $aggregateFields = [],
		array $where = [],
		array $orderBy = [],
		int $limit = Find::DefaultFetchLimit,
		int $offset = 0,
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
			implode(', ', $normalizedFields),
			'FROM',
			$tableName,
		];

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

		// @todo Add GROUP BY

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
		$querySegments[] = sprintf('LIMIT %2$d, %1$d', $limit, $offset);

		return [
			implode(' ', $querySegments),
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
		array $where = [],
		array $orderBy = [],
	): array
	{
		return static::all(
			tableName: $tableName,
			fields: $fields,
			aggregateFields: $aggregateFields,
			where: $where,
			orderBy: $orderBy,
			limit: 1
		);
	}

	/**
	 *	Creates a count query by first building a sub query with all filtering applied and counts on that result.
	 */
	public static function count(
		string $tableName,
		string $countFieldName = 'id',
		array $aggregateFields = [],
		array $where = [],
	): array
	{
		if (count($aggregateFields) > 0)
		{
			return self::complexCount(
				tableName: $tableName,
				countFieldName: $countFieldName,
				aggregateFields: $aggregateFields,
				where: $where,
			);
		}
		else
		{
			return self::simpleCount(
				tableName: $tableName,
				countFieldName: $countFieldName,
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
