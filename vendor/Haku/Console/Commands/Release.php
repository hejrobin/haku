<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Override;

use Haku\Console\Command;

use function Haku\manifest;
use function Haku\Console\Commands\Services\Release\{
	parseGitCommits,
	generateChangelog,
	updateManifest,
	isValidVersion,
	bumpVersion,
	hasUncommittedChanges,
	commitReleaseChanges,
};

class Release extends Command
{

	public function description(): string
	{
		return 'creates a new release with version bump and changelog generation';
	}

	#[Override]
	public function options(): array
	{
		return [
			'--major|create major release (x.0.0)|',
			'--minor|create minor release (0.x.0)|',
			'--patch|create patch release (0.0.x)|',
			'--set|set version manually|',
			'--from|git reference to start changelog from (tag/commit)|',
			'--message|custom message to display in changelog|',
			'--no-changelog|skip changelog generation|',
		];
	}

	public function invoke(): bool
	{
		// Check for uncommitted changes before starting
		if (hasUncommittedChanges())
		{
			$this->output->error('uncommitted changes detected!');
			$this->output->error('commit or stash your changes before creating a release');

			return false;
		}

		$pkg = manifest();
		$currentVersion = $pkg->version ?? '0.0.0';

		$willBumpMajor = array_key_exists('major', $this->arguments->arguments);
		$willBumpMinor = array_key_exists('minor', $this->arguments->arguments);
		$willBumpPatch = array_key_exists('patch', $this->arguments->arguments);
		$willSetVersion = array_key_exists('set', $this->arguments->arguments);

		$skipChangelog = array_key_exists('no-changelog', $this->arguments->arguments);

		$bumpCount = ($willBumpMajor ? 1 : 0) + ($willBumpMinor ? 1 : 0) + ($willBumpPatch ? 1 : 0) + ($willSetVersion ? 1 : 0);

		if ($bumpCount === 0)
		{
			$this->output->error('must specify version bump type: --major, --minor, --patch, or --set');

			return false;
		}

		if ($bumpCount > 1)
		{
			$this->output->error('only one version flag can be used at a time');

			return false;
		}

		// Determine new version
		$newVersion = null;

		if ($willSetVersion)
		{
			$newVersion = $this->arguments->arguments['set'];

			if (!isValidVersion($newVersion))
			{
				$this->output->error('invalid version format, expected: X.Y.Z (e.g., 1.2.3)');

				return false;
			}
		}
		else
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

			$newVersion = bumpVersion($currentVersion, $bumpType);
		}

		$customMessage = $this->arguments->arguments['message'] ?? null;

		$this->output->info(sprintf('creating release: %s → %s', $currentVersion, $newVersion));

		if (!updateManifest($this->output, $newVersion))
		{
			return false;
		}

		// Generate changelog if not skipped
		if (!$skipChangelog)
		{
			// Use --from flag if provided, otherwise use lastReleaseCommit from manifest
			$fromRef = $this->arguments->arguments['from'] ?? null;

			if ($fromRef === null && isset($pkg->lastReleaseCommit))
			{
				$fromRef = $pkg->lastReleaseCommit;
				$this->output->info(sprintf('generating changelog from last release commit: %s', substr($fromRef, 0, 7)));
			}


			$changes = parseGitCommits($fromRef);

			if (empty($changes))
			{
				$this->output->warn('no git commits found for changelog generation');
			}
			else
			{
				if (!generateChangelog($this->output, $newVersion, null, $changes, $customMessage))
				{
					$this->output->warn('changelog generation failed, but version was updated');
				}
			}
		}

		// Auto-commit the release changes
		if (!commitReleaseChanges($newVersion, $customMessage))
		{
			$this->output->error('failed to commit release changes - you may need to commit manually');

			return false;
		}

		$this->output->success(sprintf('release %s created and committed successfully', $newVersion));

		return true;
	}

}
