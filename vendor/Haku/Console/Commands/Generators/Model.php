<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Generators;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spl\Strings\snakeCaseFromCamelCase;

class Model extends Generator
{

	public function run(): bool
	{
		$model = $this->arguments->model;
		$segments = explode('/', $model);

		$model = array_pop($segments);
		$namespace = '';

		if (count($segments) > 0)
		{
			$namespace = '\\';
			$namespace .= implode('\\', $segments);
		}

		return $this->generate(
			targetRootPath: 'app/models',
			templateFileName: 'model',
			outputFilePattern: '%s.php',
			templateVariables: [
				'namespace' => $namespace,
				'model' => $model,
				'tableName' => snakeCaseFromCamelCase($model)
			],
		);
	}

}
