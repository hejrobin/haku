<?php
declare(strict_types=1);

namespace Haku\Spl\Arrays;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Port of [].any? from Ruby.
 *
 *	@link https://apidock.com/ruby/v2_5_5/Enumerable/any%3F
 */
function any(
	array $array,
	callable $callback
): bool
{
	foreach ($array as $item)
	{
		if (call_user_func($callback, $item) === true)
		{
			return true;
		}
	}

	return false;
}

function find(
	array $array,
	callable $callback
): mixed
{
	foreach ($array as $item)
	{
		if (call_user_func($callback, $item) === true)
		{
			return $item;
		}
	}

	return null;
}
