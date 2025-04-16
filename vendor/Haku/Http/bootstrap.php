<?php
declare(strict_types=1);

namespace Haku\Http;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\Mocking\getMockedJsonPayload;

function getRawRequestPayload(): string
{
	if (HAKU_ENVIRONMENT === 'test')
	{
		return getMockedJsonPayload();
	}

	return file_get_contents('php://input');
}
