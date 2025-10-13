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

use function Haku\Delegation\{
	pathToRegex,
	normalizeMiddlewarePathName,
};

spec('Delegation/bootstrap', function()
{

	describe('pathToRegex()', function()
	{

		it('converts simple path to regex', function()
		{
			$pattern = pathToRegex('/users');

			return expectAll(
				expect(str_contains($pattern, '~^'))->toBe(true),
				expect(str_contains($pattern, '$~ix'))->toBe(true),
				expect(str_contains($pattern, 'users'))->toBe(true),
			);
		});

		it('converts root path to regex', function()
		{
			$pattern = pathToRegex('/');

			return expectAll(
				expect(str_contains($pattern, '~^'))->toBe(true),
				expect(str_contains($pattern, '$~ix'))->toBe(true),
			);
		});

		it('converts path with single parameter', function()
		{
			$pattern = pathToRegex('/users/{id}');

			return expectAll(
				expect(str_contains($pattern, '(?<id>'))->toBe(true),
				expect(str_contains($pattern, 'users'))->toBe(true),
			);
		});

		it('converts parameter ending with "id" to numeric pattern', function()
		{
			$pattern = pathToRegex('/users/{userId}');

			return expectAll(
				expect(str_contains($pattern, '(?<userId>'))->toBe(true),
				expect(str_contains($pattern, '(\d+)'))->toBe(true),
			);
		});

		it('converts typed number parameter to numeric pattern', function()
		{
			$pattern = pathToRegex('/items/{count:number}');

			return expectAll(
				expect(str_contains($pattern, '(?<count>'))->toBe(true),
				expect(str_contains($pattern, '(\d+)'))->toBe(true),
			);
		});

		it('converts optional parameter', function()
		{
			$pattern = pathToRegex('/users/{id}?');

			return expectAll(
				expect(str_contains($pattern, '(?<id>'))->toBe(true),
				expect(str_contains($pattern, '(?:'))->toBe(true),
				expect(str_contains($pattern, ')?'))->toBe(true),
			);
		});

		it('converts path with multiple parameters', function()
		{
			$pattern = pathToRegex('/posts/{postId}/comments/{commentId}');

			return expectAll(
				expect(str_contains($pattern, '(?<postId>'))->toBe(true),
				expect(str_contains($pattern, '(?<commentId>'))->toBe(true),
				expect(str_contains($pattern, 'posts'))->toBe(true),
				expect(str_contains($pattern, 'comments'))->toBe(true),
			);
		});

		it('handles nested paths', function()
		{
			$pattern = pathToRegex('/api/v1/users/{id}');

			return expectAll(
				expect(str_contains($pattern, 'api'))->toBe(true),
				expect(str_contains($pattern, 'v1'))->toBe(true),
				expect(str_contains($pattern, 'users'))->toBe(true),
				expect(str_contains($pattern, '(?<id>'))->toBe(true),
			);
		});

	});

	describe('normalizeMiddlewarePathName()', function()
	{

		it('normalizes simple middleware name', function()
		{
			$normalized = normalizeMiddlewarePathName('auth');

			return expect($normalized)->toEqual('App\\Middlewares\\Auth');
		});

		it('normalizes snake_case middleware name', function()
		{
			$normalized = normalizeMiddlewarePathName('rate_limit');

			return expect($normalized)->toEqual('App\\Middlewares\\RateLimit');
		});

		it('normalizes middleware with @ prefix to framework namespace', function()
		{
			$normalized = normalizeMiddlewarePathName('@jwt');

			return expect($normalized)->toEqual('Haku\\Delegation\\Middlewares\\Jwt');
		});

		it('normalizes nested middleware path', function()
		{
			$normalized = normalizeMiddlewarePathName('api/auth');

			return expect($normalized)->toEqual('App\\Middlewares\\Api\\Auth');
		});

		it('normalizes framework middleware with path', function()
		{
			$normalized = normalizeMiddlewarePathName('@cors');

			return expect($normalized)->toEqual('Haku\\Delegation\\Middlewares\\Cors');
		});

		it('converts dash-case to PascalCase', function()
		{
			$normalized = normalizeMiddlewarePathName('custom-middleware');

			return expect($normalized)->toEqual('App\\Middlewares\\CustomMiddleware');
		});

		it('handles multiple path segments', function()
		{
			$normalized = normalizeMiddlewarePathName('api/v1/auth');

			return expect($normalized)->toEqual('App\\Middlewares\\Api\\V1\\Auth');
		});

	});

	describe('Regex pattern matching', function()
	{

		it('matches exact path', function()
		{
			$pattern = pathToRegex('/users');
			$match = preg_match($pattern, '/users');

			return expect($match)->toBe(1);
		});

		it('does not match different path', function()
		{
			$pattern = pathToRegex('/users');
			$match = preg_match($pattern, '/posts');

			return expect($match)->toBe(0);
		});

		it('matches path with parameter', function()
		{
			$pattern = pathToRegex('/users/{id}');
			$match = preg_match($pattern, '/users/123', $matches);

			return expectAll(
				expect($match)->toBe(1),
				expect($matches['id'])->toEqual('123'),
			);
		});

		it('matches path with multiple parameters', function()
		{
			$pattern = pathToRegex('/posts/{postId}/comments/{commentId}');
			$match = preg_match($pattern, '/posts/42/comments/99', $matches);

			return expectAll(
				expect($match)->toBe(1),
				expect($matches['postId'])->toEqual('42'),
				expect($matches['commentId'])->toEqual('99'),
			);
		});

		it('matches numeric parameter', function()
		{
			$pattern = pathToRegex('/users/{userId}');
			$match = preg_match($pattern, '/users/456', $matches);

			return expectAll(
				expect($match)->toBe(1),
				expect($matches['userId'])->toEqual('456'),
			);
		});

		it('does not match non-numeric for id parameter', function()
		{
			$pattern = pathToRegex('/users/{userId}');
			$match = preg_match($pattern, '/users/abc');

			return expect($match)->toBe(0);
		});

	});

});
