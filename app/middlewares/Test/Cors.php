<?php
declare(strict_types=1);

namespace App\Middlewares\Test;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Delegation\Middleware;

use Haku\Http\{
	Request,
	Headers,
	Message
};

class Cors extends Middleware
{

	public function invoke(
		Request $request,
		Message $response,
		Headers $headers
	): array
	{
		/* You can manipulate $request, $response, $headers */
		return [$request, $response, $headers];
	}

}
