<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Database\Query\{
	normalizeSet,
	normalizeConditions,
};

class Write
{

	public static function insert(
		string $tableName,
		array $values
	): array
	{
		$queryPattern = 'INSERT INTO %s SET %s';

		[$variables, $parameters] = normalizeSet(
			$tableName,
			$values,
			filterNullValues: true,
		);

		$query = sprintf($queryPattern, $tableName, implode(', ', $variables));

		return [$query, $parameters];
	}

	public static function update(
		string $tableName,
		array $values,
		array $where,
	): array
	{
		$queryPattern = 'UPDATE %s SET %s WHERE %s';

		[$variables, $parameters] = normalizeSet($tableName, $values);

		$conditions = normalizeConditions(
			$tableName,
			$where,
		);

		$query = sprintf(
			$queryPattern,
			$tableName,
			implode(', ', $variables),
			implode(' ', $conditions->where->clauses),
		);

		return [$query, $parameters + $conditions->where->parameters];
	}

	public static function restore(
		string $tableName,
		array $where
	): array
	{
		return static::update($tableName, [ 'deletedAt' => null ], $where);
	}

	public static function softDelete(
		string $tableName,
		array $where
	): array
	{
		return static::update($tableName, [ 'deletedAt' => time() ], $where);
	}

	public static function delete(
		string $tableName,
		array $where
	): array
	{
		$conditions = normalizeConditions($tableName, $where);

		$query = sprintf(
			'DELETE FROM %s WHERE %s',
			$tableName,
			implode(' ', $conditions->where->clauses)
		);

		return [$query, $conditions->where->parameters];
	}
}
