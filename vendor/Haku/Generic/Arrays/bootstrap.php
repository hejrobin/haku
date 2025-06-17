<?php
declare(strict_types=1);

namespace Haku\Generic\Arrays;

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

/**
 *	Finds a specific entry in an array based on condition constraint in callback.
 *
 *	@param array $array
 *	@param callable $callback
 *
 *	@return mixed
 */
function find(
	array $array,
	callable $callback
): mixed
{
	if (function_exists('array_find'))
	{
		return array_find($array, $callback);
	}

	foreach ($array as $item)
	{
		if (call_user_func($callback, $item) === true)
		{
			return $item;
		}
	}

	return null;
}
