<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Generators;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Generic\Strings\snakeCaseFromCamelCase;

class Migration extends Generator
{

	public function run(): bool
	{
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
			],
		);
	}

}
