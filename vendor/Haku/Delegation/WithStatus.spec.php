<?php
declare(strict_types=1);

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
	expectAll,
};

use Haku\Delegation\WithStatus;

spec('Delegation/WithStatus', function()
{

	describe('Status code registration', function()
	{

		it('creates WithStatus with status code', function()
		{
			$withStatus = new WithStatus(200);

			return expect($withStatus->getStatusCode())->toBe(200);
		});

		it('returns status code as integer', function()
		{
			$withStatus = new WithStatus(404);

			return expect(is_int($withStatus->getStatusCode()))->toBe(true);
		});

	});

	describe('Success status codes', function()
	{

		it('handles 200 OK', function()
		{
			$withStatus = new WithStatus(200);

			return expect($withStatus->getStatusCode())->toBe(200);
		});

		it('handles 201 Created', function()
		{
			$withStatus = new WithStatus(201);

			return expect($withStatus->getStatusCode())->toBe(201);
		});

		it('handles 204 No Content', function()
		{
			$withStatus = new WithStatus(204);

			return expect($withStatus->getStatusCode())->toBe(204);
		});

	});

	describe('Redirect status codes', function()
	{

		it('handles 301 Moved Permanently', function()
		{
			$withStatus = new WithStatus(301);

			return expect($withStatus->getStatusCode())->toBe(301);
		});

		it('handles 302 Found', function()
		{
			$withStatus = new WithStatus(302);

			return expect($withStatus->getStatusCode())->toBe(302);
		});

		it('handles 307 Temporary Redirect', function()
		{
			$withStatus = new WithStatus(307);

			return expect($withStatus->getStatusCode())->toBe(307);
		});

	});

	describe('Client error status codes', function()
	{

		it('handles 400 Bad Request', function()
		{
			$withStatus = new WithStatus(400);

			return expect($withStatus->getStatusCode())->toBe(400);
		});

		it('handles 401 Unauthorized', function()
		{
			$withStatus = new WithStatus(401);

			return expect($withStatus->getStatusCode())->toBe(401);
		});

		it('handles 403 Forbidden', function()
		{
			$withStatus = new WithStatus(403);

			return expect($withStatus->getStatusCode())->toBe(403);
		});

		it('handles 404 Not Found', function()
		{
			$withStatus = new WithStatus(404);

			return expect($withStatus->getStatusCode())->toBe(404);
		});

		it('handles 422 Unprocessable Entity', function()
		{
			$withStatus = new WithStatus(422);

			return expect($withStatus->getStatusCode())->toBe(422);
		});

	});

	describe('Server error status codes', function()
	{

		it('handles 500 Internal Server Error', function()
		{
			$withStatus = new WithStatus(500);

			return expect($withStatus->getStatusCode())->toBe(500);
		});

		it('handles 502 Bad Gateway', function()
		{
			$withStatus = new WithStatus(502);

			return expect($withStatus->getStatusCode())->toBe(502);
		});

		it('handles 503 Service Unavailable', function()
		{
			$withStatus = new WithStatus(503);

			return expect($withStatus->getStatusCode())->toBe(503);
		});

	});

});
