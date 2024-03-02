<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Generic\Query\Filter;
use Haku\Database\Query\Where;

use function Haku\Generic\Strings\{
	camelCaseFromSnakeCase,
	snakeCaseFromCamelCase,
};

function normalizeField(
	string $tableName,
	string $fieldName
): string
{
	return sprintf('%s.%s', $tableName, snakeCaseFromCamelCase($fieldName));
}

/**
 *	Normalizes where clauses from {@see Haku\Database\Query\Where} and {@see Haku\Database\Query\OrWhere},
 *	if any of the conditions contains an aggregated value, it is treated as a HAVING condition.
 */
function normalizeConditions(
	string $tableName,
	array $conditions,
	array $aggregateFields = [],
): object
{
	$whereClauses = [];
	$whereParameters = [];
	$havingClauses = [];
	$havingParameters = [];

	$currentWhereIndex = 0;
	$currentHavingIndex = 0;

	$glues = ['AND', 'OR'];

	$normalizeCondition = function (
		string $fieldName,
		?string $value,
		string $condition,
		string $param,
	) {
		$conditions = [];
		$parameters = [];

		if (is_null($value) === false)
		{
			$conditions[] = sprintf(
				'%1$s %2$s :%3$s',
				$fieldName,
				$condition,
				$param,
			);

			$parameters[$param] = $value;
		}
		else
		{
			$conditions[] = sprintf(
				'%1$s %2$s',
				$fieldName,
				$condition,
			);
		}

		return [$conditions, $parameters];
	};

	foreach ($conditions as $clause)
	{
		$addTo = 'where';

		[$field, $value, $condition, $glue] = $clause;

		$field = snakeCaseFromCamelCase($field);
		$fieldName = normalizeField($tableName, $field);

		if (
			count($aggregateFields) > 0 &&
			in_array($field, array_keys($aggregateFields))
		) {
			$addTo = 'having';
			$fieldName = $field;
		}

		$currentIndex = $addTo === 'where' ? $currentWhereIndex : $currentHavingIndex;
		$param = sprintf('%s_%s_%s_%d', $addTo, $tableName, $field, $currentIndex);

		[$conditions, $parameters] = $normalizeCondition(
			$fieldName,
			$value,
			$condition,
			$param,
		);

		$lastWhereClause = array_pop(array_slice($whereClauses, -1));
		$lastHavingClause = array_pop(array_slice($havingClauses, -1));

		if ($currentWhereIndex > 0 && !in_array($lastWhereClause, $glues))
		{
			$whereClauses[] = $glue;
		}

		if ($currentHavingIndex > 0 && !in_array($lastHavingClause, $glues))
		{
			$havingClauses[] = $glue;
		}

		if ($addTo === 'where')
		{
			$whereClauses = array_merge($whereClauses, $conditions);
			$whereParameters = array_merge($whereParameters, $parameters);

			$currentWhereIndex++;
		}
		else
		{
			$havingClauses = array_merge($havingClauses, $conditions);
			$havingParameters = array_merge($havingParameters, $parameters);

			$currentHavingIndex++;
		}

		$currentIndex++;
	}

	$lastWhereClause = array_pop(array_slice($whereClauses, -1));
	$lastHavingClause = array_pop(array_slice($havingClauses, -1));

	if (in_array($lastWhereClause, $glues))
	{
		$whereClauses = array_slice($whereClauses, 0, -1);
	}

	if (in_array($lastHavingClause, $glues))
	{
		$havingClauses = array_slice($havingClauses, 0, -1);
	}

	return (object) [
		'where' => (object) [
			'clauses' => $whereClauses,
			'parameters' => $whereParameters
		],
		'having' => (object) [
			'clauses' => $havingClauses,
			'parameters' => $havingParameters
		],
	];
}

/**
 *	Normalizes SET assignments
 */
function normalizeSet(
	string $tableName,
	array $parameters,
	bool $filterNullValues = false,
	string $primaryKeyName = 'id',
): array
{
	$conditions = [];

	foreach ($parameters as $parameter => $value)
	{
		if ($parameter === $primaryKeyName)
		{
			unset($parameters[$primaryKeyName]);
			continue;
		}

		if (is_null($value) && $filterNullValues)
		{
			unset($parameters[$parameter]);

			continue;
		}

		$conditions[] = sprintf(
			'%s = :%s',
			normalizeField($tableName, $parameter),
			$parameter,
		);
	}

	return [$conditions, $parameters];
}

/**
 *	Normalizes order by clauses
 */
function normalizeOrderByClauses(
	string $tableName,
	array $orderBy,
	array $aggregateFields = []
): array
{
	$conditions = [];

	foreach ($orderBy as $order)
	{
		[$field, $direction] = $order;

		if (!in_array($field, array_keys($aggregateFields)))
		{
			$field = normalizeField($tableName, $field);
		}

		$conditions[] = "{$field} {$direction}";
	}

	return $conditions;
}
