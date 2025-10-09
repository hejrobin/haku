<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Override;

use Haku\Console\Command;

use function Haku\resolvePath;
use function Haku\Generic\Strings\random;

/**
 *	Env command for haku, allows for creating and regenerating environment configs.
 */
class Env extends Command
{

	#[Override]
	public function options(): array
	{
		return [
			'--name|environment name (dev, test, prod)|required',
			'--regenerate|regenerate existing environment config|',
		];
	}

	public function description(): string
	{
		return 'creates or regenerates environment config files';
	}

	public function invoke(): bool
	{
		$args = $this->arguments->arguments;

		if (!isset($args['name']))
		{
			$this->output->error('no environment name specified, use --name dev|test|prod');

			return false;
		}

		$environment = $args['name'];
		$regenerate = isset($args['regenerate']);

		// Validate environment name
		$validEnvironments = ['dev', 'test', 'prod'];
		if (!in_array($environment, $validEnvironments))
		{
			$this->output->error(sprintf(
				'invalid environment "%s", must be one of: %s',
				$environment,
				implode(', ', $validEnvironments)
			));

			return false;
		}

		$inputPath = resolvePath('private', 'generator-templates', 'env.tmpl');
		$outputPath = resolvePath(sprintf('config.%s.php', $environment));

		// Check if file exists and regenerate flag is not set
		if (file_exists($outputPath) && !$regenerate)
		{
			$this->output->error(sprintf(
				'%s environment already configured, use --regenerate to overwrite',
				$environment
			));

			return false;
		}

		// Check if template exists
		if (!file_exists($inputPath))
		{
			$this->output->error('environment template not found');

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
				'could not create %s environment',
				$environment
			));

			return false;
		}

		if ($regenerate)
		{
			$this->output->success(sprintf(
				'regenerated environment config for: %s',
				$environment
			));
		}
		else
		{
			$this->output->success(sprintf(
				'created environment config for: %s',
				$environment
			));
		}

		return true;
	}

}
