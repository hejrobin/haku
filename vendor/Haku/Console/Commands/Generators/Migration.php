<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Generators;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Database\Migration\SchemaParser;

use function Haku\Generic\Strings\snakeCaseFromCamelCase;
use function Haku\Generic\Strings\camelCaseFromSnakeCase;

class Migration extends Generator
{

	public function run(): bool
	{
		$name = $this->arguments?->migration;
		$migrationName = camelCaseFromSnakeCase($name, true);
		$modelName = $this->arguments?->arguments['from'] ?? '';

		$default = '/* @todo Implement %s logic for %s */';

		$up = sprintf($default, 'up', $migrationName);
		$down = sprintf($default, 'down', $migrationName);
		$seed = sprintf($default, 'seed', $migrationName);

		if (strlen($name) < 5)
		{
			$this->output->error('migration name needs to be at least five characters long');

			return false;
		}

		if ($modelName)
		{
			$parser = new SchemaParser();
			$parser->parse($modelName);

			$createSql = str_replace("\n", "\n\t\t\t", $parser->toCreateSQL());

			$up = sprintf("\$db->exec(\"\n\t\t\t%s\n\t\t\t\");", $createSql);
			$down = sprintf("\$db->exec(\"%s\");", $parser->toDropSQL());
		}

		$outputFilePattern = sprintf("%s_%%s.php", date('Ymd'));
		$migrationFileName = snakeCaseFromCamelCase($name);

		return $this->generate(
			targetRootPath: 'app/migrations',
			templateFileName: 'migration',
			outputFilePattern: $outputFilePattern,
			nameArgument: $migrationFileName,
			templateVariables: [
				'migration' => $migrationName,
				'up' => $up,
				'down' => $down,
				'seed' => $seed,
			],
		);
	}

}
