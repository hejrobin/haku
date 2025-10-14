<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Services\Release;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Output;

use function Haku\resolvePath;

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
