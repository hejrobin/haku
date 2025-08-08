<?php
declare(strict_types=1);

namespace Haku\Spec\Mocking;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

$mockedJsonPayload = '';

/**
 *	Creates a mocked JSON payload used for testing payloads.
 */
function mockJsonPayload(array $data)
{
	global $mockedJsonPayload;

	$mockedJsonPayload = \json_encode($data);
}

/**
 *	Returns a mocked JSON if present, {@see mockJsonPayload}. Only used internally by the spec runner.
 */
function getMockedJsonPayload(): string
{
	global $mockedJsonPayload;

	return $mockedJsonPayload;
}

function resetMockedJsonPayload(): void
{
	global $mockedJsonPayload;

	$mockedJsonPayload = '';
}
