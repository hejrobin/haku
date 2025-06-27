<?php
declare(strict_types=1);

namespace Haku\Filesystem;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 *	Returns an interable list of files within a directory, including sub-directories
 */
function directoryList(string $target): RecursiveIteratorIterator
{
	return  new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);
}

/**
 *	Recursively deletes a folder and it's contents.
 */
function deleteDirectory(string $target, bool $keepTarget = false): bool
{
	if (file_exists($target) === false)
	{
		return false;
	}

	$entries = directoryList($target);

	foreach ($entries as $entry)
	{
		if ($entry->isDir())
		{
			$didDelete = rmdir($entry->getRealPath());

			if (!$didDelete)
			{
				return false;
			}
		}
		else
		{
			$didDelete = unlink($entry->getRealPath());

			if (!$didDelete)
			{
				return false;
			}
		}
	}

	if (!$keepTarget)
	{
		return rmdir($target);
	}

	return true;
}

/**
 *	Compares files sameness using filesize and sha1 hashing.
 */
function fileCompare(string $fileA, string $fileB): bool
{
	if (!is_file($fileA) || !is_file($fileB))
	{
		throw new InvalidArgumentException('Argument is not a file.');
	}

	if (filesize($fileA) !== filesize($fileB))
	{
		return false;
	}

	if (sha1_file($fileA) === sha1_file($fileB))
	{
		return true;
	}

	return false;
}

/**
 *	Gets and normalizes uploaded arrays.
 *
 *	@return array[]
 */
function getUploadedFiles(?array $files): array
{
	$result = [];

	if (!$files)
	{
		$files = $_FILES;
	}

	if (count($files) === 0)
	{
		return $result;
	}

	foreach($files as $outerKey => $outer)
	{
		foreach ($outer as $innerKey => $value)
		{
			$result[$innerKey][$outerKey] = $value;
		}
	}

	return $result;
}
