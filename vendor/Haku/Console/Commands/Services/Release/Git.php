<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Services\Release;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Checks if there are any uncommitted changes in the working directory.
 *
 *	@return bool True if there are uncommitted changes, false otherwise
 */
function hasUncommittedChanges(): bool
{
	exec('git status --porcelain 2>/dev/null', $output, $returnCode);

	return $returnCode === 0 && !empty($output);
}

/**
 *	Commits all changes with a release message.
 *
 *	@param string $version The version being released
 *
 *	@return bool True on success, false on failure
 */
function commitReleaseChanges(string $version, ?string $commitMessage = null): bool
{
	// Stage all changes
	exec('git add . 2>&1', $output, $returnCode);

	if ($returnCode !== 0)
	{
		return false;
	}

	// Commit with release message
	$message = "chore(release): updated version to {$version}";

	$command = sprintf('git commit -m %s ', escapeshellarg($message));

	if ($commitMessage !== null)
	{
		$command .= sprintf(' -m %s ', escapeshellarg($commitMessage));
	}

	$command .= ' 2>&1';

	exec($command, $output, $returnCode);

	return $returnCode === 0;
}

/**
 *	Parses git commits using conventional commit format into changelog categories.
 *
 *	@param string|null $fromRef Starting git reference (tag/commit), null for all commits since last tag
 *	@param string $toRef Ending git reference (default: HEAD)
 *
 *	@return array Organized changes by category
 */
function parseGitCommits(?string $fromRef = null, string $toRef = 'HEAD'): array
{
	// If no fromRef provided, try to get the last tag
	if ($fromRef === null)
	{
		exec('git describe --tags --abbrev=0 2>/dev/null', $output, $returnCode);
		$fromRef = ($returnCode === 0 && !empty($output)) ? mb_trim($output[0]) : null;
	}

	// Build git log command
	$range = $fromRef ? "{$fromRef}..{$toRef}" : $toRef;
	$command = "git log {$range} --pretty=format:'%s' --no-merges 2>/dev/null";

	exec($command, $commits, $returnCode);

	if ($returnCode !== 0)
	{
		return [];
	}

	// Map conventional commit types to Keep A Changelog categories
	$typeMap = [
		'feat' => 'added',
		'fix' => 'fixed',
		'docs' => 'changed',
		'style' => 'changed',
		'refactor' => 'changed',
		'perf' => 'changed',
		'test' => 'changed',
		'chore' => 'changed',
		'build' => 'changed',
		'ci' => 'changed',
		'revert' => 'changed',
		'security' => 'security',
		'breaking' => 'changed',
		'deprecated' => 'deprecated',
		'removed' => 'removed',
	];

	$changes = [];

	foreach ($commits as $commit)
	{
		// Parse conventional commit format: type(scope): message
		if (preg_match('/^(\w+)(?:\(([^)]+)\))?\s*:\s*(.+)$/i', $commit, $matches))
		{
			$type = strtolower($matches[1]);
			$scope = !empty($matches[2]) ? $matches[2] : null;
			$message = $matches[3];

			// Skip chore(release) commits
			if ($type === 'chore' && $scope === 'release')
			{
				continue;
			}

			// Check for breaking changes
			if (str_contains($commit, '!:') || str_contains(strtolower($message), 'breaking'))
			{
				$category = 'changed';
				$message = "**BREAKING:** {$message}";
			}
			else
			{
				$category = $typeMap[$type] ?? 'changed';
			}

			// Format the message with scope if available
			if ($scope)
			{
				$message = "**{$scope}:** {$message}";
			}

			// Capitalize first letter
			$message = mb_ucfirst($message);

			$changes[$category][] = $message;
		}
		else
		{
			// Non-conventional commit, add to changed
			$changes['changed'][] = mb_ucfirst(mb_trim($commit));
		}
	}

	return $changes;
}
