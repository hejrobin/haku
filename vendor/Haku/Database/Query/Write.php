<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Database\{
	normalizeSet,
	normalizeWhereClauses,
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

		[$conditions, $whereParameters] = normalizeWhereClauses(
			$tableName,
			$where,
		);

		$query = sprintf(
			$queryPattern,
			$tableName,
			implode(', ', $variables),
			implode(' ', $conditions),
		);

		return [$query, $parameters + $whereParameters];
	}

	public static function restore(
		string $tableName,
		array $where
	): array
	{
		return static::update($tableName, ['deletedAt' => null], $where);
	}

	public static function softDelete(
		string $tableName,
		array $where
	): array
	{
		return static::update($tableName, ['deletedAt' => time()], $where);
	}

	public static function delete(
		string $tableName,
		array $where
	): array
	{
		$queryPattern = 'DELETE FROM %s WHERE %s';

		[$conditions, $parameters] = normalizeWhereClauses($tableName, $where);

		$query = sprintf($queryPattern, $tableName, implode(' ', $conditions));

		return [$query, $parameters];
	}
}
