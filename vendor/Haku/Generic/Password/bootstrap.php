<?php
declare(strict_types=1);

namespace Haku\Generic\Password;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

function create(string $phrase): string
{
	return password_hash($phrase, PASSWORD_BCRYPT, [
		'cost' => 10,
	]);
}

function verify(string $phrase, string $hash): bool
{
	return password_verify($phrase, $hash);
}
