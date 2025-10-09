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
		// Check if generating from model (--model flag present)
		if (array_key_exists('model', $this->arguments->arguments)) {
			return $this->generateFromModel();
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
		// Model name comes from the migration argument
		$modelName = $this->arguments->migration;
		$modelClass = "App\\Models\\{$modelName}";

		// Check if model exists
		$modelFile = resolvePath('app', 'models', "{$modelName}.php");
		if (!file_exists($modelFile)) {
			$this->output->error(sprintf('model not found: %s', $modelName));
			return false;
		}

		// Load the model
		require_once $modelFile;

		if (!class_exists($modelClass)) {
			$this->output->error(sprintf('model class not found: %s', $modelClass));
			return false;
		}

		try {
			// Get table name from Entity attribute
			$reflection = new \ReflectionClass($modelClass);
			$entityAttributes = $reflection->getAttributes(\Haku\Database\Attributes\Entity::class);
			if (empty($entityAttributes)) {
				throw new \Exception("Model {$modelClass} must have #[Entity] attribute");
			}
			$entity = $entityAttributes[0]->newInstance();
			$tableName = $entity->tableName;

			// Generate SQL from model
			$sql = SchemaGenerator::generateFromModel($modelClass);

			// Wrap SQL in exec() call
			$upSqlCode = "\$db->exec(\"\n\t\t\t" . str_replace("\n", "\n\t\t\t", $sql) . "\n\t\t\");";

			// Generate DROP TABLE for down migration
			$downSqlCode = "\$db->exec(\"DROP TABLE IF EXISTS `{$tableName}`;\");";

			// Create migration name
			$migrationName = "Create{$modelName}Table";
			$migrationFileName = snakeCaseFromCamelCase($migrationName);
			$outputFilePattern = sprintf("%s_%%s.php", date('Ymd'));
			$fileName = sprintf("app/migrations/{$outputFilePattern}", $migrationFileName);
			$filePath = resolvePath(...explode('/', $fileName));

			// Create migration file with generated SQL
			$templatePath = resolvePath('private', 'generator-templates', 'migration.tmpl');
			$template = file_get_contents($templatePath);

			$templateVariables = [
				'migration' => $migrationName,
				'upSql' => $upSqlCode,
				'downSql' => $downSqlCode,
				'seedSql' => '// @todo Add seed data for ' . $tableName . ' table.',
			];

			foreach($templateVariables as $variable => $value) {
				$template = str_replace("%{$variable}%", strval($value), $template);
			}

			$bytesWritten = file_put_contents($filePath, $template);

			if ($bytesWritten === 0) {
				$this->output->error(sprintf('could not write file: %s', $fileName));
				return false;
			}

			$this->output->success(sprintf('created migration from model: %s', $fileName));
			$this->output->info('generated SQL:');
			$this->output->send($sql);

			return true;
		}
		catch (\Throwable $e) {
			$this->output->error($e->getMessage());
			return false;
		}
	}

}
