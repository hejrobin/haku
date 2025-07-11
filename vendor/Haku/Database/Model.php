<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Exception;
use JsonSerializable;
use AllowDynamicProperties;

use Haku\Database\Exceptions\{
	ModelException
};

use Haku\Database\Query\{
	Find,
	Where,
	Write
};

use Haku\Database\Mixins\Entity;

use function Haku\haku;

#[AllowDynamicProperties]
abstract class Model implements JsonSerializable
{

	use Entity;

	protected const DefaultFetchLimit = 50;

	protected Connection $connection;

	public function __construct(array $record = [])
	{
		$this->prepareEntity();
		$this->prepareEntityProperties();

		if (count($record) > 0) {
			$this->assign($record, false);
		}
	}

	public static function from(array $record = []): static
	{
		return new static($record);
	}

	/**
	 * Finds all recorfs for specific clauses.
	 *
	 *	@param bool $distinct
	 *
	 *	@param array $where
	 *	@param array $orderBy
	 *
	 *	@param int $limit
	 *	@param int $offset
	 *
	 *	@param bool $includeDeleted
	 *
	 *	@return array
	 */
	public static function findAll(
		bool $distinct = false,

		array $where = [],
		array $orderBy = [],

		int $limit = Model::DefaultFetchLimit,
		int $offset = 0,

		bool $includeDeleted = false,
	): ?array
	{
		$self = new static();
		$db = haku('db');

		if ($self->isSoftDeleteable() && !$includeDeleted) {
			$where[] = Where::null('deletedAt');
		}

		[$query, $parameters] = Find::all(
			tableName: $self->tableName,
			fields: $self->getRecordFields(),
			aggregateFields: $self->getAggregateFields(),
			where: $where,
			orderBy: $orderBy,
			limit: $limit,
			offset: $offset,
			distinct: $distinct,
		);

		return $db->fetchAll($query, $parameters) ?? [];
	}

	/**
	 *	Finds a single record.
	 *
	 *	@param array $where
	 *	@param bool $includeDeleted
	 *	@param bool $distinct
	 *
	 *	@return Model|null
	 */
	public static function findOne(
		array $where = [],
		bool $includeDeleted = false,
		bool $distinct = false,
	): ?static
	{
		$self = new static();
		$db = haku('db');

		$result = [];

		if ($self->isSoftDeleteable() && !$includeDeleted)
		{
			$where[] = Where::null('deletedAt');
		}

		[$query, $parameters] = Find::one(
			tableName: $self->tableName,
			fields: $self->getRecordFields(),
			aggregateFields: $self->getAggregateFields(),
			where: $where,
			distinct: $distinct,
		);

		$result = $db->fetch($query, $parameters);

		if (!$result) {
			return null;
		}

		return static::from($result);
	}

	public static function find(int | string $primaryKey, bool $includeDeleted = false): ?static
	{
		$self = new static();

		return static::findOne(
			[Where::is($self->primaryKeyName, $primaryKey)],
			$includeDeleted,
		);
	}

	/**
	 *	Creates a paginated resultsrt.
	 *
	 *	@param mixed $distinct
	 *	@param array $additionalFields
	 *
	 *	@param array $joins
	 *	@param array $where
	 *	@param array $orderBy
	 *
	 *	@param int $page
	 *	@param int $limit
	 *
	 *	@param bool $includeDeleted
	 *	@param mixed $countFieldName
	 *
	 *	@return array
	 */
	public static function paginate(
		bool $distinct = false,
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
		$self = new static();
		$db = haku('db');

		if ($self->isSoftDeleteable() && !$includeDeleted) {
			$where[] = Where::null('deletedAt');
		}

		if (!$countFieldName)
		{
			$countFieldName = $self->primaryKeyName;
		}

		[$countQuery, $countParams] = Find::count(
			tableName: $self->tableName,
			countFieldName: $countFieldName,
			aggregateFields: $self->getAggregateFields(),
			joins: $joins,
			where: $where,
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

		[$query, $parameters] = Find::all(
			tableName: $self->tableName,
			fields: [...$self->getRecordFields(), ...$additionalFields],
			aggregateFields: $self->getAggregateFields(),
			joins: $joins,
			where: $where,
			orderBy: $orderBy,
			limit: $limit,
			offset: $offset,
			distinct: $distinct,
		);

		$records = $db->fetchAll($query, $parameters) ?? [];

		$additionalFields = array_map(fn($field) => end(explode('.', $field)), $additionalFields);
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

	/**
	 *	This method fetches records associated to an already fetched one, in a one-to-many manner.
	 *
	 *	@example
	 *		$task = Task::find(1);
	 *		$task = SubTasks::associate($task, 'id', 'task_id', 'subTasks');
	 *
	 *	@param array $sourceRecord
	 *	@param string $sourceColumn
	 *	@param string $targetColumn
	 *	@param string $sourceRecordProperty
	 *
	 *	@return array
	 */
	public static function associate(
		array $sourceRecord,
		string $sourceColumn,
		string $targetColumn,
		string $sourceRecordProperty,
		int $limit = Model::DefaultFetchLimit,
	): array
	{
		$sourceRecords = $sourceRecord['records'];

		$identifiables = array_map('\Haku\Database\sqlValueFrom', array_column($sourceRecords, $sourceColumn));
		$inQuery = sprintf('{field} IN (%s)', implode(', ', $identifiables));

		$records = self::findAll(
			limit: $limit,
			where: [
				Where::custom($targetColumn, $inQuery)
			]
		);

		$sourceRecords = array_map(function($record) use ($records, $sourceColumn, $targetColumn, $sourceRecordProperty)
		{
			$faces = array_filter($records, function($item) use ($record, $sourceColumn, $targetColumn)
			{
				if (isset($record) && isset($item))
				{
					if (
						array_key_exists($targetColumn, $item) &&
						array_key_exists($sourceColumn, $record)
					) {
						return $item[$targetColumn] === $record[$sourceColumn];
					}

					return false;
				}
			});

			if(count($faces) > 0)
			{
				$record[$sourceRecordProperty] = array_values($faces);
			}

			return $record;
		}, $sourceRecords);

		$sourceRecord['records'] = $sourceRecords;

		return $sourceRecord;
	}

	/**
	 *	Attempts to delete a record, if it is soft-deletable it will soft delete.
	 *
	 *	@param int $primaryKey
	 *	@param bool $forceDelete
	 *
	 *	@return bool
	 */
	public static function delete(int $primaryKey, bool $forceDelete = false): bool
	{
		$self = new static();
		$db = haku('db');

		if ($self->isSoftDeleteable() && !$forceDelete)
		{
			[$query, $parameters] = Write::softDelete($self->tableName, [
				Where::is($self->primaryKeyName, $primaryKey),
			]);
		}
		else
		{
			[$query, $parameters] = Write::delete($self->tableName, [
				Where::is($self->primaryKeyName, $primaryKey),
			]);
		}

		return $db->execute($query, $parameters);
	}

	/**
	 *	Attempts to restore a soft deleted entity.
	 *
	 *	@return Model|null
	 */
	public function restore(): ?static
	{
		$self = new static();

		if ($this->isSoftDeleteable() && $this->deletedAt !== null)
		{
			$db = haku('db');

			$primaryKey = $this->getPrimaryKey();

			[$query, $parameters] = Write::restore($self->tableName, [
				Where::is($self->primaryKeyName, $primaryKey),
			]);

			$success = $db->execute($query, $parameters);

			if ($success)
			{
				return static::find($primaryKey);
			}
		}

		return null;
	}

	public function jsonSerialize(
		bool $skipValidation = false,
		array $additionalFields = []
	): mixed
	{
		if (!$skipValidation)
		{
			$errors = $this->validate();

			if (count($errors) > 0)
			{
				return null;
			}
		}

		$record = $this->getRecord(
			filterPrivate: true,
			filterAggregates: false,
			additionalFields: $additionalFields
		);

		return $this->marshalRecord($record);
	}

	/**
	 *	Alias for {@see Model::jsonSerialize}
	 */
	public function json(
		bool $skipValidation = false,
		array $additionalFields = []
	): mixed
	{
		return $this->jsonSerialize($skipValidation, $additionalFields);
	}

	/**
	 *	Calls marshaller for a field, e.g "marshalDistance"
	 */
	protected function marshalRecord(array $record): array
	{
		foreach ($record as $field => $value)
		{
			$marshaller = sprintf('marshal%s', ucfirst($field));

			if (method_exists($this, $marshaller))
			{
				$record[$field] = call_user_func_array([$this, $marshaller], [$value]);
			}
		}

		return $record;
	}

	/**
	 *	Calls unmarshaller for a field, e.g "unmarshalDistance"
	 */
	protected function unmarshalRecord(array $record): array
	{
		foreach ($record as $field => $value)
		{
			$marshaller = sprintf('unmarshal%s', ucfirst($field));

			if (method_exists($this, $marshaller))
			{
				$record[$field] = call_user_func_array([$this, $marshaller], [$value]);
			}
		}

		return $record;
	}

	/**
	 *	Calls mutator for each fields, e.g "mutateTitle".
	 *	This will override the parameter value for insert/update.
	 */
	protected function mutateRecord(array $record): array
	{
		foreach ($record as $field => $value)
		{
			$mutator = sprintf('mutate%s', ucfirst($field));

			if (method_exists($this, $mutator))
			{
				$record[$field] = call_user_func_array([$this, $mutator], [$value]);
			}
		}

		return $record;
	}

	public function hydrate(array | object $partialRecord)
	{
		$payload = $this->unmarshalRecord((array) $partialRecord);
		$this->assign($payload);
	}

	/**
	 *	Attempts to create or update model.
	 */
	public function save(
		bool $ignoreValidationStatus = false,
		bool $unmarshalBeforeSave = true,
	): ?static
	{
		$db = haku('db');

		if ($ignoreValidationStatus === false && $this->isValid === false)
		{
			throw new ModelException(
				sprintf('Cannot save %s before validation.', static::class),
			);
		}

		$record = $this->getRecord(filterTimestamps: true);

		if ($unmarshalBeforeSave)
		{
			$record = $this->unmarshalRecord($record);
		}

		$record = $this->mutateRecord($record);

		$updatedModel = null;

		if ($this->isPersistent() && method_exists($this, 'setUpdatedAt'))
		{
			$record['updatedAt'] = time();
		}
		elseif (!$this->isPersistent() && method_exists($this, 'setCreatedAt'))
		{
			$record['createdAt'] = time();
		}

		$insert = Write::insert(
			tableName: $this->tableName,
			values: $record,
			transform: $this->transformFields,
		);

		$update = Write::update(
			tableName: $this->tableName,
			values: $record,
			where: [
				Where::is($this->primaryKeyName, $record[$this->primaryKeyName]),
			],
			transform: $this->transformFields,
		);

		[$query, $parameters] = $this->isPersistent() ? $update : $insert;

		try
		{
			$result = $db->execute($query, $parameters);

			if ($this->isPersistent())
			{
				$primaryKey = $this->getPrimaryKey();
			}
			else
			{
				$primaryKey = $db->lastInsertId();
			}

			if ($result && $primaryKey)
			{
				$updatedModel = static::find($primaryKey);

				return $updatedModel;
			}
		}
		catch (Exception $exception)
		{
			throw $exception;
		}
	}

}
