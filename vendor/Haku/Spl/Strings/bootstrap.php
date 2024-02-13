<?php
declare(strict_types=1);

namespace Haku\Spl\Strings;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Hyphenates a string
 */
function hyphenate(
	string $unresolvedString,
	string $wordDelimiter = '-',
	array $wordReplacements = [],
): string
{
	if (count($wordReplacements) > 0)
	{
		$unresolvedString = str_ireplace($wordReplacements, ' ', $unresolvedString);
	}

	$resolvedString = iconv('UTF-8', 'ASCII//TRANSLIT', $unresolvedString);
	$resolvedString = preg_replace('%[^-/+|\w ]%', '', $resolvedString);
	$resolvedString = strtolower(trim($resolvedString, '-'));
	$resolvedString = preg_replace('/[\/_|+ -]+/', $wordDelimiter, $resolvedString);

	return $resolvedString;
}

function camelCaseFromSnakeCase(string $unresolved): string
{
	$resolved = trim(preg_replace('/[^a-z0-9]+/i', ' ', $unresolved));
	$resolved = ucwords($resolved);
	$resolved = str_replace(' ', '', $resolved);
	$resolved = lcfirst($resolved);

	return $resolved;
}

function snakeCaseFromCamelCase(string $unresolved): string
{
	$resolved = preg_replace('/([a-z]+)([0-9]+)/i', '$1_$2', $unresolved);
	$resolved = preg_replace('/([a-z]+)([A-Z]+)/', '$1_$2', $resolved);
	$resolved = strtolower($resolved);

	return $resolved;
}
