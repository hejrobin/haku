<?php
declare(strict_types=1);

namespace Haku\Generic\Password;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Creates a password hash using bcrypt hash.
 *
 *	@param string $phrase
 *
 *	@return string
 */
function create(string $phrase): string
{
	return password_hash($phrase, PASSWORD_BCRYPT, [
		'cost' => 10,
	]);
}

/**
 *	Verifies that a hash and phrase match.
 *
 *	@param string $phrase
 *	@param string $hash
 *
 *	@return bool
 */
function verify(string $phrase, string $hash): bool
{
	return password_verify($phrase, $hash);
}
