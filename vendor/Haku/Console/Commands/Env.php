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
			'--regenerate|regenerate existing environment config|',
		];
	}

	public function description(): string
	{
		return 'creates or regenerates environment config files';
	}

	#[Override]
	public function requiresContext(): bool
	{
		return true;
	}

	public function invoke(): bool
	{
		// Support both context (new) and --name (legacy) syntax
		$environment = $this->arguments->context ?? $this->arguments->arguments['name'] ?? null;

		if ($environment === null)
		{
			$this->output->error('no environment name specified');

			return false;
		}

		$regenerate = isset($this->arguments->arguments['regenerate']);

		$inputPath = resolvePath('private', 'generator-templates', 'env.tmpl');
		$outputPath = resolvePath(sprintf('config.%s.php', $environment));

		if (file_exists($outputPath) && !$regenerate)
		{
			$this->output->warn(sprintf(
				'%s environment already configured, use --regenerate to overwrite',
				$environment
			));

			return false;
		}

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
