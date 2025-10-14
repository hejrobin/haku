<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Override;

use Haku\Console\Command;

use function Haku\manifest;

class Version extends Command
{

	public function description(): string
	{
		return 'displays or updates the current haku version';
	}

	#[Override]
	public function options(): array
	{
		return [
			'--bump-major|increment major version (x.0.0)|',
			'--bump-minor|increment minor version (0.x.0)|',
			'--bump-patch|increment patch version (0.0.x)|',
			'--set|set version manually|',
		];
	}

	public function invoke(): bool
	{
		$pkg = manifest();
		$currentVersion = $pkg->version ?? '0.0.0';

		$willBumpMajor = array_key_exists('bump-major', $this->arguments->arguments);
		$willBumpMinor = array_key_exists('bump-minor', $this->arguments->arguments);
		$willBumpPatch = array_key_exists('bump-patch', $this->arguments->arguments);
		$willSetVersion = array_key_exists('set', $this->arguments->arguments);

		$bumpCount = ($willBumpMajor ? 1 : 0) + ($willBumpMinor ? 1 : 0) + ($willBumpPatch ? 1 : 0) + ($willSetVersion ? 1 : 0);

		if ($bumpCount > 1)
		{
			$this->output->error('only one version flag can be used at a time');

			return false;
		}

		// Handle manual version setting
		if ($willSetVersion)
		{
			$newVersion = $this->arguments->arguments['set'];

			if (!Services\isValidVersion($newVersion))
			{
				$this->output->error('invalid version format, expected: X.Y.Z (e.g., 1.2.3)');

				return false;
			}

			return Services\updateManifest($this->output, $newVersion);
		}

		// Handle version bumping
		if ($bumpCount === 1)
		{
			$bumpType = null;

			if ($willBumpMajor)
			{
				$bumpType = 'major';
			}
			elseif ($willBumpMinor)
			{
				$bumpType = 'minor';
			}
			elseif ($willBumpPatch)
			{
				$bumpType = 'patch';
			}

			$newVersion = Services\bumpVersion($currentVersion, $bumpType);

			return Services\updateManifest($this->output, $newVersion);
		}

		$this->output->output($currentVersion);

		return true;
	}

}
