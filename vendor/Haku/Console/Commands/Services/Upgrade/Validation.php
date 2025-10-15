<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Services\Upgrade;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\resolvePath;

/**
 *	Checks if the current git working directory has uncommitted changes.
 *
 *	@return array Contains 'clean' (bool) and 'status' (string) keys
 */
function checkGitStatus(): array
{
	// Check if git is available
	exec('git --version 2>/dev/null', $output, $returnCode);

	if ($returnCode !== 0)
	{
		return [
			'clean' => true,
			'status' => 'git not available',
			'has_git' => false,
		];
	}

	// Check if this is a git repository
	exec('git rev-parse --git-dir 2>/dev/null', $output, $returnCode);

	if ($returnCode !== 0)
	{
		return [
			'clean' => true,
			'status' => 'not a git repository',
			'has_git' => false,
		];
	}

	// Check for uncommitted changes
	exec('git status --porcelain 2>/dev/null', $output, $returnCode);

	if ($returnCode !== 0)
	{
		return [
			'clean' => true,
			'status' => 'could not check git status',
			'has_git' => true,
		];
	}

	$isClean = empty($output);

	return [
		'clean' => $isClean,
		'status' => $isClean ? 'no uncommitted changes' : 'nncommitted changes detected',
		'has_git' => true,
		'changes' => $output,
	];
}

/**
 *	Validates that required directories exist and are writable.
 *
 *	@return array Contains 'valid' (bool), 'errors' (array), and 'warnings' (array)
 */
function validateEnvironment(): array
{
	$errors = [];
	$warnings = [];

	$requiredDirs = [
		'vendor',
		'vendor/Haku',
		'private',
	];

	$requiredFiles = [
		'bootstrap.php',
		'index.php',
		'manifest.json',
	];

	// Check directories
	foreach ($requiredDirs as $dir)
	{
		$path = resolvePath($dir);

		if (!is_dir($path))
		{
			$errors[] = "required directory missing: {$dir}";
		}
		elseif (!is_writable($path))
		{
			$errors[] = "directory not writable: {$dir}";
		}
	}

	// Check files
	foreach ($requiredFiles as $file)
	{
		$path = resolvePath($file);

		if (!file_exists($path))
		{
			$errors[] = "required file missing: {$file}";
		}
		elseif (!is_writable($path))
		{
			$errors[] = "file not writable: {$file}";
		}
	}

	// Check PHP version
	if (version_compare(PHP_VERSION, '8.3.0', '<'))
	{
		$errors[] = 'PHP 8.3.0 or higher is required';
	}

	// Check for custom modifications
	$coreFiles = ['bootstrap.php', 'index.php'];

	foreach ($coreFiles as $file)
	{
		$path = resolvePath($file);

		if (file_exists($path))
		{
			$content = file_get_contents($path);

			// Simple heuristic: check for comments or additions that might indicate customization
			if (preg_match('/@custom|@modified|@todo/i', $content))
			{
				$warnings[] = "file may have custom modifications: {$file}";
			}
		}
	}

	// Check disk space (at least 10MB free)
	$freeSpace = disk_free_space(resolvePath(''));
	if ($freeSpace !== false && $freeSpace < 10485760)
	{
		$warnings[] = 'low disk space (less than 10MB available)';
	}

	return [
		'valid' => empty($errors),
		'errors' => $errors,
		'warnings' => $warnings,
	];
}

/**
 *	Compares current version with remote version.
 *
 *	@param string $remoteManifestPath Path to the downloaded manifest.json
 *
 *	@return array Contains 'current', 'remote', 'needs_upgrade', and 'comparison' keys
 */
function compareVersions(string $remoteManifestPath): array
{
	$currentManifest = resolvePath('manifest.json');

	$current = ['version' => 'unknown', 'valid' => false];
	$remote = ['version' => 'unknown', 'valid' => false];

	// Read current version
	if (file_exists($currentManifest))
	{
		$currentData = json_decode(file_get_contents($currentManifest), true);

		if ($currentData && isset($currentData['version']))
		{
			$current = [
				'version' => $currentData['version'],
				'valid' => true,
				'data' => $currentData,
			];
		}
	}

	// Read remote version
	if (file_exists($remoteManifestPath))
	{
		$remoteData = json_decode(file_get_contents($remoteManifestPath), true);

		if ($remoteData && isset($remoteData['version']))
		{
			$remote = [
				'version' => $remoteData['version'],
				'valid' => true,
				'data' => $remoteData,
			];
		}
	}

	$needsUpgrade = version_compare($remote['version'], $current['version'], '>');

	return [
		'current' => $current,
		'remote' => $remote,
		'needs_upgrade' => $needsUpgrade,
		'same_version' => $current['version'] === $remote['version'],
	];
}
