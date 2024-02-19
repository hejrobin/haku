<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use PDO;

class Connection extends PDO
{

	use Marshaller;
	use Statement;

	private readonly string $databaseConnectionString;

	protected array $driverOptions = [];

	/**
	 *	Prepares database connection string.
	 */
	public function __construct(
		public ConnectionType $type,
		private string $database,
		private ?string $host,
		private ?int $port,
	) {
		$dsn = $type->driverName() . ':' . $type->connectionString();

		$host = $host ?? '127.0.0.1';

		if (isset($port) === false || empty($port))
		{
			$dsn = preg_replace('/(port=%3\$d[;| ])/i', '', $dsn);
			$dsn = sprintf($dsn, $host, $database);
		}
		else
		{
			$dsn = sprintf($dsn, $host, $database, $port);
		}

		$this->databaseConnectionString = $dsn;
	}

	/**
	 *	Returns an array with default database options.
	 */
	private function defaultDatabaseOptions(): array
	{
		$databaseOptions = [
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_CLASS,
		];

		if ($this->type === ConnectionType::MySQL)
		{
			$databaseOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
		}

		return $databaseOptions;
	}

	/**
	 *	Establishes a connection to a database.
	 */
	public function connect(
		string $username,
		string $password,
		array $databaseOptions = [],
	): void
	{
		parent::__construct(
			$this->databaseConnectionString,
			$username,
			$password,
			$this->defaultDatabaseOptions() + $databaseOptions,
		);
	}

}
