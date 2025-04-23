<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Generic\Strings\{
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
		bool $isCustom = false,
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
		else if ($isCustom === false)
		{
			$conditions[] = sprintf(
				'%1$s %2$s',
				$fieldName,
				$condition,
			);
		}
		else
		{
			$condition = str_ireplace(
				['{field}'],
				[$fieldName],
				$condition
			);

			$conditions[] = $condition;
		}

		return [$conditions, $parameters];
	};

	foreach ($conditions as $clause)
	{
		$addTo = 'where';

		[$field, $value, $condition, $glue] = $clause;
		$isCustom = count($clause) === 5;

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
			$isCustom
		);

		$lastWhere = array_slice($whereClauses, -1);
		$lastWhereClause = array_pop($lastWhere);

		$lastHaving = array_slice($havingClauses, -1);
		$lastHavingClause = array_pop($lastHaving);

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

	$lastWhere = array_slice($whereClauses, -1);
	$lastWhereClause = array_pop($lastWhere);

	$lastHaving = array_slice($havingClauses, -1);
	$lastHavingClause = array_pop($lastHaving);

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
	array $transform = []
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

		$transformedParameter = ":{$parameter}";

		if (array_key_exists($parameter, $transform))
		{
			$transformedParameter = $transform[$parameter];
		}

		$conditions[] = sprintf(
			'%s = %s',
			normalizeField($tableName, $parameter),
			$transformedParameter,
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

/**
 *	Add *simple* joins, for more complex queries consider writing the SQL queries manually
 *	Joins are defined as an array of ['table' => 'bar', 'on' => ['foo_column', 'bar_column']]
 */
function normalizeSimpleJoin(
	string $tableName,
	array $joins
): array
{
	$statements = [];

	if (count($joins) > 0)
	{
		foreach($joins as $join)
		{
			if (array_key_exists('table', $join) && array_key_exists('on', $join))
			{
				[$sourceColumn, $targetColumn] = $join['on'];

				array_push(
					$statements,
					'JOIN',
					$join['table'],
					'ON',
					sprintf(
						'%s = %s',
						normalizeField($tableName, $sourceColumn),
						normalizeField($join['table'], $targetColumn)
					),
				);
			}
		}
	}

	return $statements;
}
