<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Where
{

	protected static function getGlue(): string
	{
		return 'AND';
	}

	public static function is(string $field, mixed $value): array
	{
		return [$field, (string) $value, '=', static::getGlue()];
	}

	public static function isNot(string $field, mixed $value): array
	{
		return [$field, (string) $value, '!=', static::getGlue()];
	}

	public static function greaterThan(string $field, mixed $value): array
	{
		return [$field, (string) $value, '>', static::getGlue()];
	}

	public static function greaterThanOrEqualTo(string $field, mixed $value): array
	{
		return [$field, (string) $value, '>=', static::getGlue()];
	}

	public static function lessThan(string $field, mixed $value): array
	{
		return [$field, (string) $value, '<', static::getGlue()];
	}

	public static function lessThanOrEqualTo(string $field, mixed $value): array
	{
		return [$field,(string)  $value, '<=', static::getGlue()];
	}

	public static function like(string $field, mixed $value): array
	{
		return [$field, "%$value%", 'LIKE', static::getGlue()];
	}

	public static function notLike(string $field, mixed $value): array
	{
		return [$field, "%$value%", 'NOT LIKE', static::getGlue()];
	}

	public static function null(string $field): array
	{
		return [$field, null, 'IS NULL', static::getGlue()];
	}

	public static function notNull(string $field): array
	{
		return [$field, null, 'IS NOT NULL', static::getGlue()];
	}

	public static function custom(string $field, string $pattern): array
	{
		return [$field, null, $pattern, static::getGlue(), true];
	}

}
