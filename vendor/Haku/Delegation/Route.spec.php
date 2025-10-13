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

use Haku\Delegation\Route;
use Haku\Http\Method;

spec('Delegation/Route', function()
{

	describe('Route construction', function()
	{

		it('creates route with default GET method', function()
		{
			$route = new Route('/test');

			return expectAll(
				expect($route->getPath())->toEqual('test'),
				expect($route->getMethod())->toBe(Method::Get),
			);
		});

		it('creates route with specific method', function()
		{
			$route = new Route('/api/users', Method::Post);

			return expectAll(
				expect($route->getPath())->toEqual('api/users'),
				expect($route->getMethod())->toBe(Method::Post),
			);
		});

		it('creates route with custom name', function()
		{
			$route = new Route('/test', Method::Get, 'test_route');

			return expect($route->getName())->toEqual('test_route');
		});

		it('creates route with empty name by default', function()
		{
			$route = new Route('/test');

			return expect($route->getName())->toEqual('');
		});

	});

	describe('Path cleaning', function()
	{

		it('cleans trailing slashes from path', function()
		{
			$route = new Route('/test/');

			return expect($route->getPath())->toEqual('test');
		});

		it('cleans double slashes from path', function()
		{
			$route = new Route('/test//users');

			return expect($route->getPath())->toEqual('test/users');
		});

		it('handles root path', function()
		{
			$route = new Route('/');

			return expect($route->getPath())->toEqual('');
		});

		it('removes leading slash', function()
		{
			$route = new Route('/api');

			return expect($route->getPath())->toEqual('api');
		});

	});

	describe('HTTP methods', function()
	{

		it('supports GET method', function()
		{
			$route = new Route('/test', Method::Get);

			return expect($route->getMethod())->toBe(Method::Get);
		});

		it('supports POST method', function()
		{
			$route = new Route('/test', Method::Post);

			return expect($route->getMethod())->toBe(Method::Post);
		});

		it('supports PUT method', function()
		{
			$route = new Route('/test', Method::Put);

			return expect($route->getMethod())->toBe(Method::Put);
		});

		it('supports DELETE method', function()
		{
			$route = new Route('/test', Method::Delete);

			return expect($route->getMethod())->toBe(Method::Delete);
		});

		it('supports PATCH method', function()
		{
			$route = new Route('/test', Method::Patch);

			return expect($route->getMethod())->toBe(Method::Patch);
		});

	});

	describe('Route parameters', function()
	{

		it('handles routes with parameters', function()
		{
			$route = new Route('/users/{id}');

			return expect($route->getPath())->toEqual('users/{id}');
		});

		it('handles routes with multiple parameters', function()
		{
			$route = new Route('/posts/{postId}/comments/{commentId}');

			return expect($route->getPath())->toEqual('posts/{postId}/comments/{commentId}');
		});

		it('handles routes with typed parameters', function()
		{
			$route = new Route('/users/{id:number}');

			return expect($route->getPath())->toEqual('users/{id:number}');
		});

		it('handles routes with optional parameters', function()
		{
			$route = new Route('/users/{id}?');

			return expect($route->getPath())->toEqual('users/{id}?');
		});

	});

});
