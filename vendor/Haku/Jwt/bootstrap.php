<?php
declare(strict_types=1);

namespace Haku\Jwt;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Encodes a JWT payload using HS256.
 *
 *	@throws Haku\Jwt\TokenException
 */
function encodeToken(array $context, int $maxAge = AccessTokenTTL): string
{
	$token = new Token(Algorithm::HS256);

	if (defined('HAKU_JWT_SIGNING_KEY') === false)
	{
		throw new TokenException('HAKU_JWT_SIGNING_KEY not set, add it to your config.');
	}

	$time = time();

	$token->issuedAt($time);
	$token->expiresAt($time + $maxAge);

	$token->set('ctx', $context);

	return $token->encode(Algorithm::HS256, HAKU_JWT_SIGNING_KEY);
}

/**
 *	Decodes a JWT string.
 *
 *	@throws Haku\Jwt\TokenException
 */
function decodeToken(string $authToken): Token
{
	return Token::decode($authToken, Algorithm::HS256, HAKU_JWT_SIGNING_KEY);
}

function getAuthorizationBearerToken(): ?string
{
	$headers = \getallheaders();

	if (array_key_exists('Authorization', $headers))
	{
		$token = str_ireplace('Bearer ', '', $headers['Authorization']);

		return $token;
	}

	return null;
}

function currentToken(): ?Token
{
	$token = getAuthorizationBearerToken();

	if (!$token)
	{
		return null;
	}

	return decodeToken($token);
}

function validateTokenTimestamp(int $timestamp): bool
{
	return (int) (string) $timestamp === $timestamp &&
		$timestamp <= PHP_INT_MAX &&
		$timestamp >= ~PHP_INT_MAX;
}
