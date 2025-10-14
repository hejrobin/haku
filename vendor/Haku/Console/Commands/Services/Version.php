<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Services;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Output;

use function Haku\resolvePath;

/**
 *	Formats version components into string.
 *
 *	@param int $major
 *	@param int $minor
 *	@param int $patch
 *
 *	@return string
 */
function formatVersion(int $major, int $minor, int $patch): string
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
function isValidVersion(string $version): bool
{
	return preg_match('/^\d+\.\d+\.\d+$/', $version) === 1;
}

/**
 *	Parses version string into components.
 *
 *	@param string $version
 *
 *	@return array [major, minor, patch]
 */
function parseVersion(string $version): array
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
 *	Increments version based on bump type.
 *
 *	@param string $currentVersion
 *	@param string $bumpType
 *
 *	@return string
 */
function bumpVersion(string $currentVersion, string $bumpType): string
{
	$parts = parseVersion($currentVersion);

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

	return formatVersion($parts['major'], $parts['minor'], $parts['patch']);
}

/**
 *	Reads and updates manifest.json with version.
 *
 *	@param \Haku\Console\Output $output
 *	@param string $nextVersion
 *
 *	@return bool
 */
function updateManifest(Output $output, string $nextVersion): bool
{
	$manifestPath = resolvePath('manifest.json');

	if (!file_exists($manifestPath))
	{
		$output->error('manifest.json not found');
		return false;
	}

	$manifestContent = file_get_contents($manifestPath);
	$manifest = json_decode($manifestContent, true);

	if ($manifest === null)
	{
		$output->error('invalid manifest.json format');
		return false;
	}

	$prevVersion = $manifest['version'] ?? '0.0.0';
	$manifest['version'] = $nextVersion;

	$json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

	// Convert spaces to tabs
	$json = preg_replace_callback('/^(  +)/m', function($matches) {
		return str_repeat("\t", strlen($matches[1]) / 2);
	}, $json);

	if (file_put_contents($manifestPath, $json . "\n") === false)
	{
		$output->error('failed to write manifest.json');
		return false;
	}

	$output->success(sprintf('version updated: %s → %s', $prevVersion, $nextVersion));

	return true;
}


