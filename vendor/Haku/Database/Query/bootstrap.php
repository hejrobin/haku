<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spl\Strings\{
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
 *	Normalizes where clauses from {@see Haku\Database\Query\Where} and {@see Haku\Database\Query\OrWhere}.
 */
function normalizeWhereClauses(
	string $tableName,
	array $whereClauses,
): array
{
	$conditions = [];
	$parameters = [];

	$numWhereClauses = count($whereClauses);
	$currentClauseIndex = 0;

	foreach ($whereClauses as $clause)
	{
		[$field, $value, $condition, $glue] = $clause;

		$field = snakeCaseFromCamelCase($field);
		$param = sprintf('var_%s_%s_%d', $tableName, $field, $currentClauseIndex);

		if ($currentClauseIndex > 0)
		{
			$conditions[] = $glue;
		}

		if (is_null($value) === false)
		{
			$conditions[] = sprintf(
				'%1$s %2$s :%3$s',
				normalizeField($tableName, $field),
				$condition,
				$param,
			);
			$parameters[$param] = $value;
		}
		else
		{
			$conditions[] = sprintf(
				'%1$s %2$s',
				normalizeField($tableName, $field),
				$condition,
			);
		}

		$currentClauseIndex++;
	}

	return [$conditions, $parameters];
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
