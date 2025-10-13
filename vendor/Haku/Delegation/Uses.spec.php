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

use Haku\Delegation\Uses;

spec('Delegation/Uses', function()
{

	describe('Middleware registration', function()
	{

		it('creates Uses with single middleware', function()
		{
			$uses = new Uses('auth');
			$middlewares = $uses->getMiddlewares();

			return expectAll(
				expect(count($middlewares))->toBe(1),
				expect($middlewares[0])->toEqual('auth'),
			);
		});

		it('creates Uses with multiple middlewares', function()
		{
			$uses = new Uses('auth', 'cors', 'rate_limit');
			$middlewares = $uses->getMiddlewares();

			return expectAll(
				expect(count($middlewares))->toBe(3),
				expect($middlewares[0])->toEqual('auth'),
				expect($middlewares[1])->toEqual('cors'),
				expect($middlewares[2])->toEqual('rate_limit'),
			);
		});

		it('creates Uses with no middlewares', function()
		{
			$uses = new Uses();
			$middlewares = $uses->getMiddlewares();

			return expect(count($middlewares))->toBe(0);
		});

		it('returns middlewares as array', function()
		{
			$uses = new Uses('test');

			return expect(is_array($uses->getMiddlewares()))->toBe(true);
		});

	});

	describe('Middleware naming', function()
	{

		it('accepts middleware with forward slash paths', function()
		{
			$uses = new Uses('api/auth');
			$middlewares = $uses->getMiddlewares();

			return expect($middlewares[0])->toEqual('api/auth');
		});

		it('accepts middleware with @ prefix', function()
		{
			$uses = new Uses('@jwt');
			$middlewares = $uses->getMiddlewares();

			return expect($middlewares[0])->toEqual('@jwt');
		});

		it('accepts middleware with snake_case names', function()
		{
			$uses = new Uses('rate_limit');
			$middlewares = $uses->getMiddlewares();

			return expect($middlewares[0])->toEqual('rate_limit');
		});

		it('accepts middleware with dash names', function()
		{
			$uses = new Uses('custom-middleware');
			$middlewares = $uses->getMiddlewares();

			return expect($middlewares[0])->toEqual('custom-middleware');
		});

	});

});
