<?php
declare(strict_types=1);

namespace Haku\Database\Mixins;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Database\Model;

use Haku\Database\Query\{
	Find,
	Where,
};

use Haku\Database\Exceptions\ModelException;

use function Haku\haku;
use function Haku\Database\Query\normalizeField;

/**
 *	Searchable assumes that a model (e.g Task) has a search table with a primary key, a foreign key (e.g task_id) and a FULLTEXT field.
 *	It will do a fulltext search on that table, and join associated model data. The result will always be paginated, and matches {{@see Model::paginate}}.
 */
trait Searchable
{

	public static function search(
		array $criteria,

		array $additionalFields = [],

		array $joins = [],
		array $where = [],
		array $orderBy = [],

		int $page = 1,
		int $limit = Model::DefaultFetchLimit,

		bool $includeDeleted = false,
		?string $countFieldName = null,
	): ?array
	{
		if (
			array_key_exists('on', $criteria) === false ||
			array_key_exists('couple', $criteria) === false ||
			array_key_exists('keywords', $criteria) === false
		) {
			throw new ModelException('Invalid search criteria.');
		}

		$self = new static();
		$db = haku('db');

		if ($self->isSoftDeleteable() && !$includeDeleted) {
			$where[] = Where::null('deletedAt');
		}

		if (!$countFieldName)
		{
			$countFieldName = $self->primaryKeyName;
		}

		$searchableTableName = $self->tableName . '_search';

		$searchableIdentifyColumn = normalizeField(
			tableName: $searchableTableName,
			fieldName: sprintf("%s_%s", $self->tableName, $criteria['couple'])
		);

		$joins = [
			[
				'table' => $self->tableName,
				'on' => [ $searchableIdentifyColumn, $criteria['couple'] ]
			],
			...$joins
		];

		$where = [
			Where::custom(
				sprintf('%s.keywords', $searchableTableName),
				sprintf("MATCH({field}) AGAINST('%s' IN BOOLEAN MODE)", implode(' ', $criteria['keywords']))
			),
			...$where,
		];

		[$countQuery, $countParams] = Find::count(
			tableName: $searchableTableName,
			countFieldName: $countFieldName,
			aggregateFields: $self->getAggregateFields(),
			where: $where,
			joins: $joins,
		);

		$numRecords = $db->fetchColumn($countQuery, $countParams);
		$pageCount = (int) ceil($numRecords / $limit);

		$prevPage = $page - 1;
		$nextPage = $page + 1;

		$offset = $prevPage * $limit;

		if ($prevPage <= 0)
		{
			$prevPage = null;
		}

		if ($nextPage > $pageCount)
		{
			$nextPage = $pageCount;
		}

		if ($page === $pageCount)
		{
			$nextPage = null;
		}

		$fields = [...$self->getRecordFields(), ...$additionalFields];

		[$query, $parameters] = Find::all(
			tableName: $self->tableName,
			fields: $fields,
			aggregateFields: $self->getAggregateFields(),
			joins: $joins,
			where: $where,
			orderBy: $orderBy,
			limit: $limit,
			offset: $offset,
			overrideFromTable: $searchableTableName
		);

		$records = $db->fetchAll($query, $parameters) ?? [];
		$records = array_map(fn($record) => static::from($record)->json(additionalFields: $additionalFields), $records);

		return [
			'pagination' => [
				'pageCount' => $pageCount,
				'page' => $page,
				'prevPage' => $prevPage,
				'nextPage' => $nextPage,
			],
			'meta' => [
				'numRecordsTotal' => $numRecords,
				'numRecordsPerPage' => $limit,
				'numRecordsOnCurrentPage' => count($records),
			],
			'records' => $records,
		];
	}

}
