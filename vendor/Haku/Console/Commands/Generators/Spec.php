<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Generators;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Spec extends Generator
{

	public function run(): bool
	{
		$targetRootPath = 'vendor';

		if (array_key_exists('app', $this->arguments->arguments))
		{
			$target = $this->arguments->arguments['app'];

			if (\str_ends_with($target, 's'))
			{
				$target = substr($target, 0, -1);
			}

			if (
				$target !== 'spec' &&
				!in_array($target, AvailableGenerators::list())
			) {
				return false;
			}

			$targetRootPath = "app/{$target}s";
		}

		return $this->generate(
			templateFileName: 'spec',
			outputFilePattern: '%s.spec.php',
			targetRootPath: $targetRootPath,
			templateVariables: [
				'spec' => $this->arguments->spec,
			],
		);
	}

}
