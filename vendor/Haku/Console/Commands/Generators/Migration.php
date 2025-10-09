<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Generators;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Database\SchemaGenerator;

use function Haku\Generic\Strings\snakeCaseFromCamelCase;
use function Haku\resolvePath;

class Migration extends Generator
{

	public function run(): bool
	{
		if (array_key_exists('model', $this->arguments->arguments))
		{
			return $this->generateFromModel();
		}

		if (!property_exists($this->arguments, 'migration') || empty($this->arguments->migration))
		{
			$this->output->error('migration name is required');

			return false;
		}

		$migrationName = $this->arguments->migration;
		$migrationFileName = snakeCaseFromCamelCase($migrationName);

		$outputFilePattern = sprintf("%s_%%s.php", date('Ymd'));

		return $this->generate(
			targetRootPath: 'app/migrations',
			templateFileName: 'migration',
			outputFilePattern: $outputFilePattern,
			nameArgument: $migrationFileName,
			templateVariables: [
				'migration' => $migrationName,
				'upSql' => '// @todo Add up migration logic for ' . $migrationName . '.',
				'downSql' => '// @todo Add down migration logic for ' . $migrationName . '.',
				'seedSql' => '// @todo Add seed logic for ' . $migrationName . '.',
			],
		);
	}

	protected function generateFromModel(): bool
	{
		$modelName = $this->arguments->arguments['model'] ?? null;

		if (empty($modelName))
		{
			$this->output->error('--model flag requires a model name');

			return false;
		}

		$modelClass = "App\\Models\\{$modelName}";
		$modelFile = resolvePath('app', 'models', "{$modelName}.php");

		if (!file_exists($modelFile))
		{
			$this->output->error(sprintf('model not found: %s', $modelName));

			return false;
		}

		require_once $modelFile;

		if (!class_exists($modelClass))
		{
			$this->output->error(sprintf('model class not found: %s', $modelClass));

			return false;
		}

		try
		{
			$reflection = new \ReflectionClass($modelClass);
			$entityAttributes = $reflection->getAttributes(\Haku\Database\Attributes\Entity::class);

			if (empty($entityAttributes))
			{
				throw new \Exception("Model {$modelClass} must have #[Entity] attribute");
			}

			$entity = $entityAttributes[0]->newInstance();
			$tableName = $entity->tableName;

			// Generate SQL from model
			$sql = SchemaGenerator::generateFromModel($modelClass);

			$upSqlCode = "\$db->exec(\"\n\t\t\t" . str_replace("\n", "\n\t\t\t", $sql) . "\n\t\t\");";
			$downSqlCode = "\$db->exec(\"DROP TABLE IF EXISTS `{$tableName}`;\");";

			$migrationName = "Create{$modelName}Table";
			$migrationFileName = snakeCaseFromCamelCase($migrationName);
			$outputFilePattern = sprintf("%s_%%s.php", date('Ymd'));

			$fileName = sprintf("app/migrations/{$outputFilePattern}", $migrationFileName);
			$filePath = resolvePath(...explode('/', $fileName));

			$templatePath = resolvePath('private', 'generator-templates', 'migration.tmpl');
			$template = file_get_contents($templatePath);

			$templateVariables = [
				'migration' => $migrationName,
				'upSql' => $upSqlCode,
				'downSql' => $downSqlCode,
				'seedSql' => '// @todo Add seed data for ' . $tableName . ' table.',
			];

			foreach($templateVariables as $variable => $value)
			{
				$template = str_replace("%{$variable}%", strval($value), $template);
			}

			$bytesWritten = file_put_contents($filePath, $template);

			if ($bytesWritten === 0)
			{
				$this->output->error(sprintf('could not write file: %s', $fileName));

				return false;
			}

			$this->output->success(sprintf('created migration from model: %s', $fileName));

			return true;
		}
		catch (\Throwable $e)
		{
			$this->output->error($e->getMessage());

			return false;
		}
	}

}
