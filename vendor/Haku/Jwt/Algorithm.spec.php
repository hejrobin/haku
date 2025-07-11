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

use Haku\Jwt\Algorithm;

use Haku\Jwt\Exceptions\AlgorithmException;

spec('Jwt\Algorithm', function()
{

	describe('Algorithm integrity', function ()
	{

		it('has at least one algorithm available', function ()
		{
			return expect(Algorithm::getAvailableAlgorithms())
				->size()
				->toBeGreaterThan(0);
		});

		it('has required HS256 algorithm', function ()
		{
			return expect(Algorithm::isAvailable('HS256'))->toBeTrue();
		});

		it('can return an existing algorithm', function ()
		{
			return expectAll(
				expect('\\Haku\\Jwt\\Algorithm::get')
					->withArguments(['HS256'])
					->not()->toThrow(AlgorithmException::class),
				expect(Algorithm::get('HS256'))->toHavePropertyValue('crypt', 'SHA256'),
				expect(Algorithm::get('HS256'))->toHavePropertyValue('protocol', 'hash_hmac'),
			);
		});

		it('throws exception on invalid algorithm', function ()
		{
			return expect('\\Haku\\Jwt\\Algorithm::get')
				->withArguments(['FAKE1337'])
				->toThrow(AlgorithmException::class);
		});

	});

});
