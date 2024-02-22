<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Exception;
use JsonSerializable;

use Haku\Database\Exceptions\{
	DatabaseException,
	ModelException
};

use Haku\Database\Query\{
	Find,
	Where,
	Write
};

use Haku\Database\Mixins\Entity;

use function Haku\haku;
use function Haku\Spl\Strings\camelCaseFromSnakeCase;

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

	public static function findAll(
		array $whereClauses = [],
		int $limit = Model::DefaultFetchLimit,
		int $offset = 0,
		bool $includeDeleted = false,
	): ?array
	{
		$self = new static();

		$result = [];

		if ($self->isSoftDeleteable() && !$includeDeleted) {
			$whereClauses[] = Where::null('deletedAt');
		}

		[$query, $parameters] = Find::all(
			$self->tableName,
			$self->getRecordFields(),
			$whereClauses,
			$limit,
			$offset
		);

		return haku('db')->fetchAll($query, $parameters);
	}

	public static function findOne(
		array $whereClauses = [],
		bool $includeDeleted = false,
	): ?static
	{
		$self = new static();

		$result = [];

		if ($self->isSoftDeleteable() && !$includeDeleted)
		{
			$whereClauses[] = Where::null('deletedAt');
		}

		[$query, $parameters] = Find::one(
			$self->tableName,
			$self->getRecordFields(),
			$whereClauses,
			1,
		);

		$result = haku('db')->fetch($query, $parameters);

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

	public static function paginate(
		array $whereClauses = [],
		int $page = 1,
		int $limit = Model::DefaultFetchLimit,
		bool $includeDeleted = false,
	): ?array
	{
		$self = new static();

		$result = [];

		if ($self->isSoftDeleteable() && !$includeDeleted) {
			$whereClauses[] = Where::null('deletedAt');
		}

		[$countQuery, $countParams] = Find::count($self->tableName, $self->primaryKeyName, $whereClauses);

		$numRecords = haku('db')->fetchColumn($countQuery, $countParams);
		$pageCount = ceil($numRecords / $limit);

		$prevPage = $page - 1;
		$nextPage = $page + 1;

		$offset = $prevPage * $limit;

		if ($prevPage <= 1)
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
			$self->tableName,
			$self->getRecordFields(),
			$whereClauses,
			$limit,
			$offset,
		);

		$records = haku('db')->fetchAll($query, $parameters);

		return [
			'pagination' => [
				'count' => $numRecords,
				'pageCount' => $pageCount,
				'page' => $page,
				'prevPage' => $prevPage,
				'nextPage' => $nextPage,
			],
			'records' => $records,
		];
	}

	public static function delete(int $primaryKey): bool
	{
		$self = new static();

		if ($self->isSoftDeleteable())
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

		return haku('db')->execute($query, $parameters);
	}

	public function restore(): ?static
	{
		if ($this->isSoftDeleteable() && $this->deletedAt !== null)
		{
			$primaryKey = $this->getPrimaryKey();

			[$query, $parameters] = Write::restore($self->tableName, [
				Where::is($self->primaryKeyName, $primaryKey),
			]);

			$success = haku('db')->execute($query, $parameters);

			if ($success)
			{
				return static::find($primaryKey);
			}
		}

		return null;
	}

	public function jsonSerialize(): mixed
	{
		$errors = $this->validate();

		if (count($errors) > 0)
		{
			return null;
		}

		return $this->getRecord(filterPrivate: true);
	}

	/**
	 *	Alias for {@see Model::jsonSerialize}
	 */
	public function json(): mixed
	{
		return $this->jsonSerialize();
	}

	/**
	 *	Calls marshaller for a field, e.g "marshalPassword"
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
	 *	Attempts to create or update model.
	 */
	public function save(bool $ignoreValidationStatus = false): ?static
	{
		if ($ignoreValidationStatus === false && $this->isValid === false)
		{
			throw new ModelException(
				sprintf('Cannot save %s before validation.', static::class),
			);
		}

		if ($ignoreValidationStatus === false && $this->isValid === false)
		{
			throw new ModelException(
				sprintf('Cannot save %s, validation failed.', static::class),
			);
		}

		$record = $this->getRecord(filterTimestamps: true);
		$record = $this->marshalRecord($record);

		$updatedModel = null;

		if ($this->isPersistent() && property_exists($this, 'updatedAt'))
		{
			$record['updatedAt'] = time();
		}
		elseif (!$this->isPersistent() && property_exists($this, 'createdAt'))
		{
			$record['createdAt'] = time();
		}

		$insert = Write::insert($this->tableName, $record);

		$update = Write::update($this->tableName, $record, [
			Where::is($this->primaryKeyName, $record[$this->primaryKeyName]),
		]);

		[$query, $parameters] = $this->isPersistent() ? $update : $insert;

		try
		{
			$result = haku('db')->execute($query, $parameters);

			if ($this->isPersistent())
			{
				$primaryKey = $this->getPrimaryKey();
			}
			else
			{
				$primaryKey = haku('db')->lastInsertId();
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
