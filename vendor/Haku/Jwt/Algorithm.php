<?php
declare(strict_types=1);

namespace Haku\Jwt;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use ReflectionClass;

use Haku\Jwt\Exceptions\AlgorithmException;

class Algorithm
{

	public const HS256 = 'HS256';
	public const HS384 = 'HS384';
	public const HS512 = 'HS512';

	public const HMAC = 'hash_hmac';

	protected static $availableAlgorithms = [
		'HS256' => ['crypt' => 'SHA256', 'protocol' => self::HMAC],
		'HS384' => ['crypt' => 'SHA384', 'protocol' => self::HMAC],
		'HS512' => ['crypt' => 'SHA512', 'protocol' => self::HMAC],
	];

	public static function getAvailableAlgorithms(): array
	{
		$ref = new ReflectionClass(__CLASS__);

		return array_keys($ref->getConstants());
	}

	public static function isAvailable(string $algorithm): bool
	{
		return in_array($algorithm, self::getAvailableAlgorithms());
	}

	public static function get(string $algorithm): object
	{
		if (self::isAvailable($algorithm) === false)
		{
			throw new AlgorithmException('Invalid algorithm.');
		}

		return (object) self::$availableAlgorithms[$algorithm];
	}

}
