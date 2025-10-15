<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;
use Haku\Console\Ansi;
use Haku\Filesystem\Archiver;

use function Haku\resolvePath;
use function Haku\Filesystem\deleteDirectory;

use function Haku\Console\Commands\Services\Upgrade\{
	createBackup,
	restoreBackup,
	checkGitStatus,
	validateEnvironment,
	compareVersions,
	analyzeChanges,
	generateChangeSummary,
	formatFileSize
};

class Upgrade extends Command
{

	private string $identifier = 'haku-main';

	private bool $dryRun = false;

	private bool $createBackupFlag = false;

	private bool $skipValidation = false;

	private ?string $backupPath = null;

	public function description(): string
	{
		return 'updates Haku framework from latest release';
	}

	public function options(): array
	{
		return [
			'--dry-run|preview changes without applying them|',
			'--backup|create a backup before upgrading|',
			'--skip-validation|skip pre-upgrade validation checks|',
			'--force|force upgrade even if versions are the same|',
		];
	}

	private function source(): string
	{
		return 'https://github.com/hejrobin/haku/archive/main.zip';
	}

	private function target(): string
	{
		return resolvePath("private/{$this->identifier}.zip");
	}

	private function coreFiles(): array
	{
		return [
			"{$this->identifier}/bootstrap.php",
			"{$this->identifier}/index.php",
			"{$this->identifier}/manifest.json",
			"{$this->identifier}/LICENSE.md",
			"{$this->identifier}/README.md",
		];
	}

	protected function runPreflightChecks(): bool
	{
		$this->output->output('running pre-flight checks...');

		// Check environment
		$validation = validateEnvironment();

		if (!$validation['valid'])
		{
			$this->output->error('environment validation failed:');

			foreach ($validation['errors'] as $error)
			{
				$this->output->error("  • {$error}");
			}

			return false;
		}

		if (!empty($validation['warnings']))
		{
			$this->output->warn('warnings detected:');

			foreach ($validation['warnings'] as $warning)
			{
				$this->output->warn("  • {$warning}");
			}
		}

		// Check git status
		$gitStatus = checkGitStatus();

		if ($gitStatus['has_git'] && !$gitStatus['clean'])
		{
			$this->output->warn('git working directory has uncommitted changes');
			$this->output->warn('consider committing or stashing changes before upgrading');

			if (!$this->dryRun && !array_key_exists('force', $this->arguments->arguments))
			{
				$this->output->break();
				$this->output->output('Use --force to upgrade anyway, or --dry-run to preview changes');

				return false;
			}
		}

		$this->output->success('all pre-flight checks passed');

		return true;
	}

	protected function download(): void
	{
		$bytes = file_put_contents($this->target(), file_get_contents($this->source()));

		if ($bytes === 0)
		{
			$this->output->error('could not fetch repository');
		}
	}

	protected function extractSource(): void
	{
		if (file_exists($this->target()))
		{
			$zip = new Archiver();

			if ($zip->open($this->target()) === true)
			{
				$this->output->output('extracting source code...');

				// Firstly extract all the core files.
				$zip->extractTo(resolvePath('private'), $this->coreFiles());

				$errors = $zip->extractDirectoryTo(
					resolvePath("private/{$this->identifier}/private"),
					"{$this->identifier}/private"
				);

				$zip->extractDirectoryTo(
					resolvePath("private/{$this->identifier}/vendor"),
					"{$this->identifier}/vendor"
				);

				$zip->close();
			}
			else
			{
				$this->output->error('could not extract source code');
			}
		}
	}

	protected function applyChanges(): void
	{
		$files = $this->coreFiles();

		foreach ($files as $file)
		{
			$source = resolvePath("private/{$file}");
			$target = resolvePath(basename($file));

			rename($source, $target);
		}

		deleteDirectory(resolvePath("private/generator-templates"));

		rename(
			resolvePath("private/{$this->identifier}/private/generator-templates"),
			resolvePath("private/generator-templates"),
		);

		deleteDirectory(resolvePath("vendor/Haku"));

		$source = resolvePath("private/{$this->identifier}/vendor/Haku");
		$target = resolvePath("vendor/Haku");

		rename($source, $target);
	}

	protected function showVersionComparison(): bool
	{
		$remoteManifest = resolvePath("private/{$this->identifier}/manifest.json");

		if (!file_exists($remoteManifest))
		{
			$this->output->warn('could not find remote manifest.json');

			return true;
		}

		$comparison = compareVersions($remoteManifest);

		$needsUpgrade = $comparison['needs_upgrade'];

		$currentVersionColor = $needsUpgrade ? Ansi::Off : Ansi::Yellow;
		$remoteVersionColor = $needsUpgrade ? Ansi::Off : Ansi::Green;

		$currentVersion = sprintf(
			"local version: %s",
			$this->output->format($comparison['current']['version'], $currentVersionColor)
		);

		$remoteVersion = sprintf(
			"remote version: %s",
			$this->output->format($comparison['remote']['version'], $remoteVersionColor)
		);

		$this->output->info($currentVersion);
		$this->output->info($remoteVersion);

		$this->output->break();

		if ($comparison['same_version'])
		{
			$this->output->warn('already on the latest version.');

			if (!array_key_exists('force', $this->arguments->arguments))
			{
				$this->output->info('use --force to reinstall anyway.');

				return false;
			}

			$this->output->info('continuing with --force flag...');
		}
		elseif (!$comparison['needs_upgrade'])
		{
			$this->output->warn("current version is newer than remote ({$comparison['current']['version']} > {$comparison['remote']['version']}).");

			if (!array_key_exists('force', $this->arguments->arguments))
			{
				$this->output->info('use --force to downgrade anyway.');

				return false;
			}

			$this->output->info('continuing with --force flag...');
		}
		else
		{
			$this->output->success('new version available!');
		}

		$this->output->break();

		return true;
	}

	protected function showChangeSummary(): void
	{
		$this->output->info('analyzing changes...');

		$changes = analyzeChanges($this->identifier, $this->coreFiles());
		$summary = generateChangeSummary($changes);

		$this->output->break();

		$this->output->output('upgrade summary:');

		$this->output->output($this->output->indent() . "new files:       {$summary['new_files']}");
		$this->output->output($this->output->indent() . "modified files:  {$summary['modified_files']}");
		$this->output->output($this->output->indent() . "unchanged files: {$summary['unchanged_files']}");
		$this->output->output($this->output->indent() . "total files:     {$summary['total_files']}");

		if ($summary['size_change'] !== 0)
		{
			$direction = $summary['size_increasing'] ? 'increase' : 'decrease';
			$this->output->output($this->output->indent() ."size change:      {$summary['size_change_formatted']} {$direction}");
		}

		$this->output->break();

		// Show new files
		if (!empty($changes['new']))
		{
			$this->output->info('new files:');

			foreach ($changes['new'] as $file)
			{
				$size = isset($file['size']) ? ' (' . formatFileSize($file['size']) . ')' : '';
				$this->output->info("+ {$file['file']}{$size}");
			}

			$this->output->break();
		}

		// Show modified files
		if (!empty($changes['modified']))
		{
			$this->output->info('modified files:');

			foreach ($changes['modified'] as $file)
			{
				$sizeInfo = '';

				if (isset($file['size_old']) && isset($file['size_new']))
				{
					$oldSize = formatFileSize($file['size_old']);
					$newSize = formatFileSize($file['size_new']);

					$sizeInfo = " ({$oldSize} → {$newSize})";
				}

				$this->output->info("~ {$file['file']}{$sizeInfo}");
			}

			$this->output->break();
		}
	}

	protected function performBackup(): bool
	{
		$this->output->output('creating backup...');

		$this->backupPath = createBackup();

		if ($this->backupPath === false)
		{
			$this->output->error('failed to create backup');

			return false;
		}

		$this->output->success("backup created: {$this->backupPath}");

		return true;
	}

	protected function rollback(): void
	{
		if ($this->backupPath === null)
		{
			$this->output->error('no backup available for rollback');

			return;
		}

		$this->output->output('rolling back changes...');

		if (restoreBackup($this->backupPath))
		{
			$this->output->success('rollback successful');
		}
		else
		{
			$this->output->error('rollback failed, please restore manually from: ' . $this->backupPath);
		}
	}

	protected function cleanup(): void
	{
		$this->output->output('cleaning up temporary files...');
		$this->output->break();

		deleteDirectory(resolvePath("private/{$this->identifier}"));
		unlink($this->target());
	}

	public function invoke(): bool
	{
		if (PHP_OS_FAMILY === 'Windows')
		{
			$this->output->error('windows support for this command is still pending...');

			return false;
		}

		// Parse flags
		$this->dryRun = array_key_exists('dry-run', $this->arguments->arguments);
		$this->createBackupFlag = array_key_exists('backup', $this->arguments->arguments);
		$this->skipValidation = array_key_exists('skip-validation', $this->arguments->arguments);

		if ($this->dryRun)
		{
			$this->output->info($this->output->format('dry run, no changes will be applied', Ansi::Underline));
			$this->output->break();
		}

		// Run pre-flight checks
		if (!$this->skipValidation)
		{
			if (!$this->runPreflightChecks())
			{
				return false;
			}

			$this->output->break();
		}

		// Create backup if requested
		if ($this->createBackupFlag && !$this->dryRun)
		{
			if (!$this->performBackup())
			{
				return false;
			}

			$this->output->break();
		}

		// Download and extract
		$this->output->output('downloading latest haku files...');

		try
		{
			$this->download();
			$this->extractSource();

			// Show version comparison
			$this->output->break();

			if (!$this->showVersionComparison())
			{
				$this->cleanup();

				return false;
			}

			// Show changes summary
			$this->output->break();
			$this->showChangeSummary();

			// In dry-run mode, stop here
			if ($this->dryRun)
			{
				$this->output->break();

				$this->output->info($this->output->format('dry run, no changes applied', Ansi::Underline));
				$this->output->output('run without --dry-run to apply these changes.');
				$this->cleanup();

				return true;
			}

			// Apply changes
			$this->output->break();
			$this->output->output('applying changes...');

			$this->applyChanges();
			$this->cleanup();

			$this->output->break();
			$this->output->success('upgrade completed successfully!');

			if ($this->backupPath !== null)
			{
				$this->output->output("backup available at: {$this->backupPath}");
			}

			return true;
		}
		catch (\Throwable $e)
		{
			$this->output->error("upgrade failed: {$e->getMessage()}");

			// Attempt rollback if backup exists
			if ($this->backupPath !== null)
			{
				$this->output->break();
				$this->rollback();
			}

			$this->cleanup();

			return false;
		}
	}

}
