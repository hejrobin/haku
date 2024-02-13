<?php
declare(strict_types=1);

namespace Haku\Delegation;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Http\{
	Request,
	Headers,
	Message
};

abstract class Middleware
{

	abstract public function invoke(
		Request $request,
		Message $response,
		Headers $headers
	): array;

}
