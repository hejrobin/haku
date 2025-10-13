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

use Haku\Delegation\WithHeaders;

spec('Delegation/WithHeaders', function()
{

	describe('Header registration', function()
	{

		it('creates WithHeaders with single header', function()
		{
			$withHeaders = new WithHeaders(['Content-Type' => 'application/json']);
			$headers = $withHeaders->getHeaders();

			return expectAll(
				expect(count($headers))->toBe(1),
				expect($headers['Content-Type'])->toEqual('application/json'),
			);
		});

		it('creates WithHeaders with multiple headers', function()
		{
			$withHeaders = new WithHeaders([
				'Content-Type' => 'application/json',
				'X-Custom-Header' => 'custom-value',
				'Cache-Control' => 'no-cache',
			]);
			$headers = $withHeaders->getHeaders();

			return expectAll(
				expect(count($headers))->toBe(3),
				expect($headers['Content-Type'])->toEqual('application/json'),
				expect($headers['X-Custom-Header'])->toEqual('custom-value'),
				expect($headers['Cache-Control'])->toEqual('no-cache'),
			);
		});

		it('creates WithHeaders with empty array', function()
		{
			$withHeaders = new WithHeaders([]);
			$headers = $withHeaders->getHeaders();

			return expect(count($headers))->toBe(0);
		});

		it('returns headers as array', function()
		{
			$withHeaders = new WithHeaders(['Test' => 'value']);

			return expect(is_array($withHeaders->getHeaders()))->toBe(true);
		});

	});

	describe('Common HTTP headers', function()
	{

		it('handles Content-Type header', function()
		{
			$withHeaders = new WithHeaders(['Content-Type' => 'text/html']);

			return expect($withHeaders->getHeaders()['Content-Type'])->toEqual('text/html');
		});

		it('handles Authorization header', function()
		{
			$withHeaders = new WithHeaders(['Authorization' => 'Bearer token123']);

			return expect($withHeaders->getHeaders()['Authorization'])->toEqual('Bearer token123');
		});

		it('handles Cache-Control header', function()
		{
			$withHeaders = new WithHeaders(['Cache-Control' => 'max-age=3600']);

			return expect($withHeaders->getHeaders()['Cache-Control'])->toEqual('max-age=3600');
		});

		it('handles custom X- headers', function()
		{
			$withHeaders = new WithHeaders(['X-Request-ID' => 'abc-123']);

			return expect($withHeaders->getHeaders()['X-Request-ID'])->toEqual('abc-123');
		});

	});

	describe('Header values', function()
	{

		it('preserves header values as strings', function()
		{
			$withHeaders = new WithHeaders(['X-Count' => '42']);

			return expect($withHeaders->getHeaders()['X-Count'])->toEqual('42');
		});

		it('handles headers with special characters', function()
		{
			$withHeaders = new WithHeaders(['X-Special' => 'value; charset=utf-8']);

			return expect($withHeaders->getHeaders()['X-Special'])->toEqual('value; charset=utf-8');
		});

	});

});
