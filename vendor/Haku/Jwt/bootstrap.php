<?php
declare(strict_types=1);

namespace Haku\Jwt;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Jwt\Exceptions\TokenException;

/**
 *	Encodes a JWT payload using HS256.
 *
 *	@throws \Haku\Jwt\Exceptions\TokenException
 */
function encodeToken(array $context, int $maxAge = 0): string
{
	$token = new Token(Algorithm::HS256);

	if (!$maxAge)
	{
		$maxAge = intval(HAKU_JWT_TOKEN_TTL ?? '60');
	}

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
 *	@throws \Haku\Jwt\Exceptions\TokenException
 */
function decodeToken(string $authToken): Token
{
	return Token::decode($authToken, Algorithm::HS256, HAKU_JWT_SIGNING_KEY);
}

/**
 *	Parses Authorization header and returns Bearer if present.
 *
 *	@return ?string
 */
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

/**
 *	Returns current token from header if set.
 *
 *	@return Token|null
 */
function currentToken(): ?Token
{
	$token = getAuthorizationBearerToken();

	if (!$token)
	{
		return null;
	}

	return decodeToken($token);
}

/**
 *	Validates whether token timestamp is valid.
 *
 *	@param int $timestamp
 *
 *	@return bool
 */
function validateTokenTimestamp(int $timestamp): bool
{
	return (int) (string) $timestamp === $timestamp &&
		$timestamp <= PHP_INT_MAX &&
		$timestamp >= ~PHP_INT_MAX;
}

/**
 *	Generates a random hash, useful for hash tokens
 *
 *	@return string
 */
function generateRefreshToken(): string
{
	return bin2hex(random_bytes(64));
}
