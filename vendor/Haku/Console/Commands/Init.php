<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

use function Haku\resolvePath;
use function Haku\Generic\Strings\random;

class Init extends Command
{

	#[Override]
	public function options(): array
	{
		return [
			'--dev|creates dev environment|',
			'--test|creates test environment|',
			'--prod|creates production environment|',
		];
	}

	public function description(): string
	{
		return 'initializes haku';
	}

	public function invoke(): bool
	{
		$args = $this->arguments->arguments;

		if (count($args) === 0)
		{
			$this->output->error('no environment defined');

			return false;
		}

		$environment = array_shift(array_keys($args));

		$inputPath = resolvePath('private', 'generator-templates', 'env.tmpl');
		$outputPath = resolvePath(sprintf('config.%s.php', $environment));

		if (file_exists($outputPath))
		{
			$this->output->error(sprintf(
				'%s envirionment already configured',
				$environment
			));

			return false;
		}

		$contents = file_get_contents($inputPath);

		$templateVariables = [
			'signingKey' => random(),
			'jwtSigningKey' => random(),
		];

		foreach($templateVariables as $variable => $value)
		{
			$contents = str_replace("%{$variable}%", strval($value), $contents);
		}

		$bytes = file_put_contents($outputPath, $contents);

		if ($bytes === 0)
		{
			$this->output->error(sprintf(
				'could not create %s envirionment',
				$environment
			));

			return false;
		}

		$this->output->success(sprintf(
			'created environment config for: %s',
			$environment
		));

		return true;
	}

}
