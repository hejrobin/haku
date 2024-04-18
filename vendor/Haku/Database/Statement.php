<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use PDO;
use PDOException;

use Haku\Database\Exceptions\{
	DatabaseException,
	ModelException
};

trait Statement
{

	/**
	 *	Executes a prepared statement.
	 *
	 *	@see https://www.php.net/manual/en/pdo.prepare.php
	 *	@see https://www.php.net/manual/en/pdostatement.execute
	 */
	public function execute(string $query, array $parameters): ?bool
	{
		try
		{
			$statement = $this->prepare($query);
			$result = $statement->execute($parameters);

			return $result !== false;
		}
		catch (PDOException $exception)
		{
			throw new DatabaseException($exception->getMessage());
		}
	}

	/**
	 *	Fetches and marshals single result from database.
	 */
	public function fetch(string $query, array $parameters): ?array
	{
		try
		{
			$statement = $this->prepare($query);
			$statement->execute($parameters);

			$result = $statement->fetch(PDO::FETCH_ASSOC);

			if (!$result) return null;

			return $this->marshal($result);
		}
		catch (PDOException $exception)
		{
			throw new DatabaseException($exception->getMessage());
		}
	}

	/**
	 *	Fetches and marshals result set from database.
	 */
	public function fetchAll(string $query, array $parameters): ?array
	{
		try
		{
			$statement = $this->prepare($query);
			$statement->execute($parameters);

			$result = $statement->fetchAll(PDO::FETCH_ASSOC);

			if (!$result || count($result) === 0) {
				return null;
			}

			return array_map(
				function (array $record)
				{
					return $this->marshal($record);
				},
				$result
			);
		}
		catch (PDOException $exception)
		{
			throw new ModelException($exception->getMessage());
		}
	}

	public function fetchColumn(
		string $query,
		array $parameters,
		int $columnIndex = 0
	): mixed
	{
		try
		{
			$statement = $this->prepare($query);
			$statement->execute($parameters);

			$result = $statement->fetchColumn($columnIndex);

			return $result;
		}
		catch (PDOException $exception)
		{
			throw new DatabaseException($exception->getMessage());
		}
	}

}
