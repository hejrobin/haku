<?php
declare(strict_types=1);

namespace Haku\Console\Commands\Services\Release;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Output;

use function Haku\resolvePath;

/**
 *	Generates a changelog entry using Keep A Changelog format.
 *
 *	@param \Haku\Console\Output $output
 *	@param string $version
 *	@param string $date
 *	@param array $changes
 *
 *	@return bool
 */
function generateChangelog(Output $output, string $version, string $date = null, array $changes = []): bool
{
	$changelogPath = resolvePath('CHANGELOG.md');
	$date ??= date('Y-m-d');

	// Initialize changelog if it doesn't exist
	if (!file_exists($changelogPath))
	{
		$header = <<<MARKDOWN
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

MARKDOWN;

		if (file_put_contents($changelogPath, $header) === false)
		{
			$output->error('failed to create CHANGELOG.md');
			return false;
		}
	}

	// Read existing changelog
	$content = file_get_contents($changelogPath);

	// Build new version entry
	$entry = "\n## [{$version}] - {$date}\n";

	// Add change categories if provided
	$categories = ['Added', 'Changed', 'Deprecated', 'Removed', 'Fixed', 'Security'];

	foreach ($categories as $category)
	{
		$categoryKey = strtolower($category);

		if (isset($changes[$categoryKey]) && !empty($changes[$categoryKey]))
		{
			$entry .= "\n### {$category}\n";

			foreach ($changes[$categoryKey] as $change)
			{
				$entry .= "- {$change}\n";
			}
		}
	}

	// If no specific changes provided, add placeholder
	if (empty($changes))
	{
		$entry .= "\n### Added\n";
		$entry .= "- Initial changes for this version\n";
	}

	$entry .= "\n";

	// Insert new entry after the header (before first ## or at end)
	if (preg_match('/^(# Changelog.*?)(\n## )/ms', $content, $matches))
	{
		// Insert before first version entry
		$content = "{$matches[1]}{$entry}{$matches[2]}";
		$content .= substr($content, strlen($matches[0]));
	}
	else
	{
		// Append to end if no version entries exist yet
		$content .= $entry;
	}

	// Write updated changelog
	if (file_put_contents($changelogPath, $content) === false)
	{
		$output->error('failed to write CHANGELOG.md');
		return false;
	}

	$output->success(sprintf('changelog updated for version %s', $version));

	return true;
}
