<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Override;

use Haku\Console\Command;

use function Haku\{
	manifest,
	resolvePath
};

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

	/**
	 *	Parses version string into components.
	 *
	 *	@param string $version
	 *
	 *	@return array [major, minor, patch]
	 */
	protected function parseVersion(string $version): array
	{
		if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches))
		{
			return [0, 0, 0];
		}

		return [
			'major' => (int) $matches[1],
			'minor' => (int) $matches[2],
			'patch' => (int) $matches[3],
		];
	}

	/**
	 *	Formats version components into string.
	 *
	 *	@param int $major
	 *	@param int $minor
	 *	@param int $patch
	 *
	 *	@return string
	 */
	protected function formatVersion(int $major, int $minor, int $patch): string
	{
		return sprintf('%d.%d.%d', $major, $minor, $patch);
	}

	/**
	 *	Validates version string format.
	 *
	 *	@param string $version
	 *
	 *	@return bool
	 */
	protected function isValidVersion(string $version): bool
	{
		return preg_match('/^\d+\.\d+\.\d+$/', $version) === 1;
	}

	/**
	 *	Increments version based on bump type.
	 *
	 *	@param string $currentVersion
	 *	@param string $bumpType
	 *
	 *	@return string
	 */
	protected function bumpVersion(string $currentVersion, string $bumpType): string
	{
		$parts = $this->parseVersion($currentVersion);

		switch ($bumpType)
		{
			case 'major':
				$parts['major']++;
				$parts['minor'] = 0;
				$parts['patch'] = 0;
				break;

			case 'minor':
				$parts['minor']++;
				$parts['patch'] = 0;
				break;

			case 'patch':
				$parts['patch']++;
				break;
		}

		return $this->formatVersion($parts['major'], $parts['minor'], $parts['patch']);
	}

	/**
	 *	Updates manifest.json with new version.
	 *
	 *	@param string $newVersion
	 *
	 *	@return bool
	 */
	protected function updateManifest(string $newVersion): bool
	{
		$manifestPath = resolvePath('manifest.json');

		if (!file_exists($manifestPath))
		{
			$this->output->error('manifest.json not found');
			return false;
		}

		$manifestContent = file_get_contents($manifestPath);
		$manifest = json_decode($manifestContent, true);

		if ($manifest === null)
		{
			$this->output->error('invalid manifest.json format');
			return false;
		}

		$oldVersion = $manifest['version'] ?? '0.0.0';
		$manifest['version'] = $newVersion;

		// Pretty print with tabs (to match existing format)
		$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

		// Convert spaces to tabs
		$json = preg_replace_callback('/^(  +)/m', function($matches) {
			return str_repeat("\t", strlen($matches[1]) / 2);
		}, $json);

		if (file_put_contents($manifestPath, $json . "\n") === false)
		{
			$this->output->error('failed to write manifest.json');
			return false;
		}

		$this->output->success(sprintf('version updated: %s → %s', $oldVersion, $newVersion));

		return true;
	}

	public function invoke(): bool
	{
		$pkg = manifest();
		$currentVersion = $pkg->version ?? '0.0.0';

		$hasBumpMajor = array_key_exists('bump-major', $this->arguments->arguments);
		$hasBumpMinor = array_key_exists('bump-minor', $this->arguments->arguments);
		$hasBumpPatch = array_key_exists('bump-patch', $this->arguments->arguments);

		$hasSetVersion = array_key_exists('set', $this->arguments->arguments);

		// Count how many bump flags are set
		$bumpCount = ($hasBumpMajor ? 1 : 0) + ($hasBumpMinor ? 1 : 0) + ($hasBumpPatch ? 1 : 0) + ($hasSetVersion ? 1 : 0);

		if ($bumpCount > 1)
		{
			$this->output->error('only one version flag can be used at a time');
			return false;
		}

		// Handle manual version setting
		if ($hasSetVersion)
		{
			$newVersion = $this->arguments->arguments['set'];

			if (!$this->isValidVersion($newVersion))
			{
				$this->output->error('invalid version format, expected: X.Y.Z (e.g., 1.2.3)');
				return false;
			}

			return $this->updateManifest($newVersion);
		}

		// Handle version bumping
		if ($bumpCount === 1)
		{
			$bumpType = null;

			if ($hasBumpMajor)
			{
				$bumpType = 'major';
			}
			elseif ($hasBumpMinor)
			{
				$bumpType = 'minor';
			}
			elseif ($hasBumpPatch)
			{
				$bumpType = 'patch';
			}

			$newVersion = $this->bumpVersion($currentVersion, $bumpType);

			return $this->updateManifest($newVersion);
		}

		// No flags, just display current version
		$this->output->output($currentVersion);

		return true;
	}

}
