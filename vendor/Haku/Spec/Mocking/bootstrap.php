<?php
declare(strict_types=1);

namespace Haku\Spec\Mocking;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

$mockedJsonPayload = '';

function mockJsonPayload(array $data)
{
	global $mockedJsonPayload;

	$mockedJsonPayload = \json_encode($data);
}

function getMockedJsonPayload(): string
{
	global $mockedJsonPayload;

	return $mockedJsonPayload;
}
