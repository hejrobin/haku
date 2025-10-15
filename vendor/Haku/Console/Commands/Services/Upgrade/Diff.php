<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Services\Upgrade;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\resolvePath;

/**
 *	Analyzes files that will be modified during upgrade.
 *
 *	@param string $identifier The temporary directory identifier (e.g., 'haku-main')
 *	@param array $coreFiles List of core files to check
 *
 *	@return array Contains 'new', 'modified', 'unchanged', and 'total' keys with file lists
 */
function analyzeChanges(string $identifier, array $coreFiles): array
{
	$new = [];
	$modified = [];
	$unchanged = [];

	// Check core files
	foreach ($coreFiles as $file)
	{
		$sourcePath = resolvePath("private/{$file}");
		$targetFile = basename($file);
		$targetPath = resolvePath($targetFile);

		if (!file_exists($sourcePath))
		{
			continue;
		}

		if (!file_exists($targetPath))
		{
			$new[] = [
				'file' => $targetFile,
				'type' => 'core',
			];
		}
		else
		{
			// Compare file contents
			$sourceHash = md5_file($sourcePath);
			$targetHash = md5_file($targetPath);

			if ($sourceHash !== $targetHash)
			{
				$modified[] = [
					'file' => $targetFile,
					'type' => 'core',
					'size_old' => filesize($targetPath),
					'size_new' => filesize($sourcePath),
				];
			}
			else
			{
				$unchanged[] = $targetFile;
			}
		}
	}

	// Check vendor/Haku directory
	$sourceVendor = resolvePath("private/{$identifier}/vendor/Haku");
	$targetVendor = resolvePath("vendor/Haku");

	if (is_dir($sourceVendor))
	{
		$vendorChanges = compareDirectories($sourceVendor, $targetVendor, 'vendor/Haku');
		$new = array_merge($new, $vendorChanges['new']);
		$modified = array_merge($modified, $vendorChanges['modified']);
		$unchanged = array_merge($unchanged, $vendorChanges['unchanged']);
	}

	// Check generator-templates directory
	$sourceTemplates = resolvePath("private/{$identifier}/private/generator-templates");
	$targetTemplates = resolvePath("private/generator-templates");

	if (is_dir($sourceTemplates))
	{
		$templateChanges = compareDirectories($sourceTemplates, $targetTemplates, 'private/generator-templates');
		$new = array_merge($new, $templateChanges['new']);
		$modified = array_merge($modified, $templateChanges['modified']);
		$unchanged = array_merge($unchanged, $templateChanges['unchanged']);
	}

	return [
		'new' => $new,
		'modified' => $modified,
		'unchanged' => $unchanged,
		'total' => count($new) + count($modified) + count($unchanged),
	];
}

/**
 *	Recursively compares two directories.
 *
 *	@param string $sourceDir Source directory path
 *	@param string $targetDir Target directory path
 *	@param string $basePath Base path for relative naming
 *
 *	@return array Contains 'new', 'modified', and 'unchanged' arrays
 */
function compareDirectories(string $sourceDir, string $targetDir, string $basePath): array
{
	$new = [];
	$modified = [];
	$unchanged = [];

	if (!is_dir($sourceDir))
	{
		return compact('new', 'modified', 'unchanged');
	}

	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
		\RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ($iterator as $file)
	{
		if ($file->isFile())
		{
			$sourcePath = $file->getPathname();
			$relativePath = str_replace($sourceDir . '/', '', $sourcePath);
			$targetPath = $targetDir . '/' . $relativePath;
			$displayPath = $basePath . '/' . $relativePath;

			if (!file_exists($targetPath))
			{
				$new[] = [
					'file' => $displayPath,
					'type' => 'file',
					'size' => filesize($sourcePath),
				];
			}
			else
			{
				$sourceHash = md5_file($sourcePath);
				$targetHash = md5_file($targetPath);

				if ($sourceHash !== $targetHash)
				{
					$modified[] = [
						'file' => $displayPath,
						'type' => 'file',
						'size_old' => filesize($targetPath),
						'size_new' => filesize($sourcePath),
					];
				}
				else
				{
					$unchanged[] = $displayPath;
				}
			}
		}
	}

	return compact('new', 'modified', 'unchanged');
}

/**
 *	Formats file size in human-readable format.
 *
 *	@param int $bytes Size in bytes
 *
 *	@return string Formatted size string
 */
function formatFileSize(int $bytes): string
{
	$units = ['B', 'KB', 'MB', 'GB'];
	$i = 0;

	while ($bytes >= 1024 && $i < count($units) - 1)
	{
		$bytes /= 1024;
		$i++;
	}

	return round($bytes, 2) . ' ' . $units[$i];
}

/**
 *	Generates a summary report of changes.
 *
 *	@param array $changes Array from analyzeChanges()
 *
 *	@return array Summary statistics
 */
function generateChangeSummary(array $changes): array
{
	$summary = [
		'new_files' => count($changes['new']),
		'modified_files' => count($changes['modified']),
		'unchanged_files' => count($changes['unchanged']),
		'total_files' => $changes['total'],
	];

	// Calculate size changes
	$totalSizeChange = 0;

	foreach ($changes['modified'] as $file)
	{
		if (isset($file['size_old']) && isset($file['size_new']))
		{
			$totalSizeChange += ($file['size_new'] - $file['size_old']);
		}
	}

	foreach ($changes['new'] as $file)
	{
		if (isset($file['size']))
		{
			$totalSizeChange += $file['size'];
		}
	}

	$summary['size_change'] = $totalSizeChange;
	$summary['size_change_formatted'] = formatFileSize(abs($totalSizeChange));
	$summary['size_increasing'] = $totalSizeChange > 0;

	return $summary;
}
