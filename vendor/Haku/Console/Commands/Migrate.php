<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

use Haku\Database\Connection;
use function Haku\resolvePath;


/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Override;

use Haku\Console\Command;
use Haku\Database\Migration;

use function Haku\haku;

/**
 *	A very simplistic database migration handler, this will create a table in the database to keep track of migrations.
 */
class Migrate extends Command
{

	public function description(): string
	{
		return 'runs any existing migrations';
	}

	#[Override]
	public function options(): array
	{
		return [
			'--down|reverts the last migration|',
			'--seed|runs seed method after migrations|'
		];
	}

	protected function prepareDatabase()
	{
		try
		{
			$db = haku('db');

			$db->exec("
				CREATE TABLE IF NOT EXISTS migrations (
					id INT AUTO_INCREMENT PRIMARY KEY,
					migration_file VARCHAR(255) NOT NULL UNIQUE,
					applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
				)
			");
		}
		catch (\Throwable $exception)
		{
			$this->output->error('could not run migrations');
			$this->output->error($exception->getMessage());
		}
	}

	private function getMigrations(): array
	{
		$migrations = glob(resolvePath('app/migrations/*.php'));
		sort($migrations);

		return $migrations;
	}

	private function getAppliedMigrations(Connection $db): array
	{
		try
		{
			$appliedMigrations = $db->query("SELECT migration_file FROM migrations")->fetchAll(\PDO::FETCH_COLUMN);

			return $appliedMigrations;
		}
		catch (\Throwable $exception)
		{
			$this->output->error('could not run migrations');
			$this->output->error($exception->getMessage());
		}

		return [];
	}

	private function runMigrations(): bool
	{
		$db = haku('db');
		$withSeed = array_key_exists('seed', $this->arguments->arguments);

		$numApplied = 0;

		$migrations = $this->getMigrations();
		$numMigrations = count($migrations);

		$appliedMigrations = $this->getAppliedMigrations($db);
		$numAppliedMigrations = count($appliedMigrations);

		$numUnmigrated = $numMigrations - $numAppliedMigrations;
		$numAppliedMigrations = 0;

		if ($numUnmigrated === 0)
		{
			$this->output->info("no applicable migrations found");
			return true;
		}
		else
		{
			$this->output->info(sprintf("found %d migration(s)", $numUnmigrated));
		}

		$autoCommit = $db->getAttribute(\PDO::ATTR_AUTOCOMMIT);
		$didDisableAutoCommit = false;

		if ($autoCommit)
		{
			$db->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
			$didDisableAutoCommit = true;
		}

		foreach($migrations as $file)
		{
			if (in_array($file, $appliedMigrations))
			{
				$this->output->info(sprintf('migration "%s" already applied, skipping...', $file));
				continue;
			}

			$migrationFile = basename($file);
			$migration = require_once $file;

			if (!$migration instanceof Migration)
			{
				$this->output->info(sprintf('"%s" is not a valid migration class', $migrationFile));

				return false;
			}

			try
			{
				$db->beginTransaction();

				$migration->up($db);

				$statement = $db->prepare("INSERT INTO `migrations` (`migration_file`) VALUES (:migrationFile)");
				$statement->bindParam(':migrationFile', $migrationFile);
				$statement->execute();

				$db->commit();

				$numAppliedMigrations++;

				$this->output->success(sprintf('applied migration "%s"', $migrationFile));

				// Run seed if --seed flag is set and method exists
				if ($withSeed && method_exists($migration, 'seed'))
				{
					try
					{
						$this->output->info(sprintf('seeding "%s"', $migrationFile));
						$migration->seed($db);
						$this->output->success(sprintf('seeded "%s"', $migrationFile));
					}
					catch (\Throwable $seedException)
					{
						$this->output->warn(sprintf('seed failed for "%s": %s', $migrationFile, $seedException->getMessage()));
					}
				}
			}
			catch (\Throwable $exception)
			{
				if ($db->inTransaction())
				{
					$db->rollBack();
				}

				$this->output->error(sprintf('could not apply migration "%s"', $migrationFile));
				$this->output->error($exception->getMessage());
			}
		}

		if ($didDisableAutoCommit)
		{
			$db->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
		}

		return $numAppliedMigrations > 0;
	}

	protected function revertLastMigration(): bool
	{
		$db = haku('db');

		$autoCommit = $db->getAttribute(\PDO::ATTR_AUTOCOMMIT);
		$didDisableAutoCommit = false;

		if ($autoCommit)
		{
			$db->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
			$didDisableAutoCommit = true;
		}

		$migrationFile = $db->query("SELECT migration_file FROM migrations ORDER BY id DESC LIMIT 1")->fetchColumn();

		if (!$migrationFile)
		{
			$this->output->info('no migrations to revert');

			return false;
		}

		$this->output->info('reverting last migration...');

		$migration = require_once resolvePath(sprintf('app/migrations/%s', $migrationFile));

		if (!$migration instanceof Migration)
		{
			$this->output->info(sprintf('"%s" is not a valid migration class', $migrationFile));

			return false;
		}

		try
		{
			if (!$db->inTransaction())
			{
				$db->beginTransaction();
			}

			$migration->down($db);

			$statement = $db->prepare("DELETE FROM `migrations` WHERE `migration_file` = :migrationFile");
			$statement->bindParam(':migrationFile', $migrationFile);
			$statement->execute();

			$db->commit();

			$this->output->success(sprintf('reverted "%s"', $migrationFile));

			return true;
		}
		catch (\Throwable $exception)
		{
			if ($db->inTransaction())
			{
				$db->rollBack();
			}

			$this->output->error(sprintf('could not roll back migration "%s"', $migrationFile));
			$this->output->error($exception->getMessage());
		}

		if ($didDisableAutoCommit)
		{
			$db->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);
		}

		return true;
	}

	public function invoke(): bool
	{
		$this->prepareDatabase();

		$isDownMigrate = array_key_exists('down', $this->arguments->arguments);

		if ($isDownMigrate)
		{
			return $this->revertLastMigration();
		}
		else
		{
			return $this->runMigrations();
		}
	}

}
