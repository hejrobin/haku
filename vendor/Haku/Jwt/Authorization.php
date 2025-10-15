<?php
declare(strict_types=1);

namespace Haku\Jwt;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Authorization
{

	public static function make(int $identifier, string $scope): string
	{
		return encodeToken([
			'identifier' => $identifier,
			'scope' => $scope,
		]);
	}

	public static function getCurrentPayload(): ?array
	{
		$token = currentToken();

		if (!$token)
		{
			return null;
		}

		return $token->getPayload();
	}

	public static function getCurrentContext(): ?array
	{
		$payload = self::getCurrentPayload();
		$ctx = $payload['ctx'] ?? null;

		return $ctx;
	}

	public static function verifyIdentifier(int $identifier): bool
	{
		$payload = self::getCurrentPayload();

		if ($payload['identifier'] === $identifier)
		{
			return true;
		}

		return false;
	}

	public static function verifyScope(array $allowedScopes): bool
	{
		$ctx = self::getCurrentContext();

		if (
			$ctx &&
			count($allowedScopes) > 0 &&
			in_array($ctx['scope'], $allowedScopes)
		) {
			return true;
		}

		return false;
	}

}
