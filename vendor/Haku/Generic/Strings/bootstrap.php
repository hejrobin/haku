<?php
declare(strict_types=1);

namespace Haku\Generic\Strings;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Hyphenates a string by normalizing accents, whitespace and combines it into a hyphenated string.
 *
 *	@param string $unresolvedString
 *	@param string $wordDelimiter = '-'
 *	@param array $wordReplacements
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

function camelCaseFromSnakeCase(string $unresolved, bool $uppercaseFirstLetter = false): string
{
	$resolved = mb_trim(preg_replace('/[^a-z0-9]+/i', ' ', $unresolved));
	$resolved = ucwords($resolved);
	$resolved = str_replace(' ', '', $resolved);
	$resolved = mb_lcfirst($resolved);

	if ($uppercaseFirstLetter)
	{
		$resolved = mb_ucfirst($resolved);
	}

	return $resolved;
}

function snakeCaseFromCamelCase(string $unresolved): string
{
	$resolved = preg_replace('/([a-z]+)([0-9]+)/i', '$1_$2', $unresolved);
	$resolved = preg_replace('/([a-z]+)([A-Z]+)/', '$1_$2', $resolved);
	$resolved = strtolower($resolved);

	return $resolved;
}

function encodeBase64Url(string $value): string
{
	return mb_rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function decodeBase64Url(string $value): string
{
	return base64_decode(
		str_pad(strtr($value, '-_', '+/'), strlen($value) % 4, '=', STR_PAD_RIGHT),
	);
}

function random(int $byteSize = 16): string
{
	return base64_encode(random_bytes($byteSize));
}
