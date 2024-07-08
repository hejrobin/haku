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

	public static function verifyIdentifier(int $identifier): bool
	{
		$token = currentToken();

		if (!$token)
		{
			return false;
		}

		$payload = $token->getPayload();

		if ($payload['identifier'] === $identifier)
		{
			return true;
		}

		return false;
	}

	public static function verifyScope(array $allowedScopes): bool
	{
		$token = currentToken();

		if (!$token)
		{
			return false;
		}

		$payload = $token->getPayload();
		$ctx = $payload['ctx'] ?? null;

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
