<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Services\Release;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Output;

use function Haku\resolvePath;

/**
 *	Gets the current git commit hash.
 *
 *	@return string|null The current commit hash or null if not in a git repository
 */
function getCurrentGitHash(): ?string
{
	exec('git rev-parse HEAD 2>/dev/null', $output, $returnCode);

	if ($returnCode !== 0 || empty($output))
	{
		return null;
	}

	return trim($output[0]);
}

/**
 *	Reads and updates manifest.json with version and git hash.
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

	// Store the current git hash for the next release
	$gitHash = getCurrentGitHash();

	if ($gitHash !== null)
	{
		$manifest['lastReleaseCommit'] = $gitHash;
	}

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
