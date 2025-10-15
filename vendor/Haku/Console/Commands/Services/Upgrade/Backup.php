<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Services\Upgrade;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Filesystem\Archiver;

use function Haku\resolvePath;

/**
 *	Creates a backup of core framework files before upgrade.
 *
 *	@param string | null $backupName Optional custom backup name (default: backup-YYYYMMDD-HHMMSS)
 *
 *	@return string | false Path to backup file on success, false on failure
 */
function createBackup(?string $backupName = null): string | false
{
	$timestamp = date('Ymd-His');
	$backupName ??= "backup-{$timestamp}";
	$backupPath = resolvePath("private/{$backupName}.zip");

	$filesToBackup = [
		'bootstrap.php',
		'index.php',
		'manifest.json',
		'haku',
		'haku-init',
		'LICENSE.md',
		'README.md',
		'vendor/Haku',
		'private/generator-templates',
	];

	$zip = new Archiver();

	if ($zip->open($backupPath, \ZipArchive::CREATE) !== true)
	{
		return false;
	}

	foreach ($filesToBackup as $file)
	{
		$fullPath = resolvePath($file);

		if (!file_exists($fullPath))
		{
			continue;
		}

		if (is_dir($fullPath))
		{
			$zip->addDirectory($fullPath, $file);
		}
		else
		{
			$zip->addFile($fullPath, $file);
		}
	}

	$zip->close();

	return file_exists($backupPath) ? $backupPath : false;
}

/**
 *	Restores files from a backup archive.
 *
 *	@param string $backupPath Path to the backup zip file
 *
 *	@return bool True on success, false on failure
 */
function restoreBackup(string $backupPath): bool
{
	if (!file_exists($backupPath))
	{
		return false;
	}

	$zip = new Archiver();

	if ($zip->open($backupPath) !== true)
	{
		return false;
	}

	$zip->extractTo(resolvePath(''));
	$zip->close();

	return true;
}

/**
 *	Lists available backup files.
 *
 *	@return array List of backup files with their metadata
 */
function listBackups(): array
{
	$privateDir = resolvePath('private');
	$backups = [];

	if (!is_dir($privateDir))
	{
		return $backups;
	}

	$files = scandir($privateDir);

	foreach ($files as $file)
	{
		if (preg_match('/^backup-(\d{8}-\d{6})\.zip$/', $file, $matches))
		{
			$fullPath = "{$privateDir}/{$file}";
			$backups[] = [
				'name' => $file,
				'path' => $fullPath,
				'timestamp' => $matches[1],
				'size' => filesize($fullPath),
				'date' => date('Y-m-d H:i:s', filemtime($fullPath)),
			];
		}
	}

	usort($backups, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

	return $backups;
}
