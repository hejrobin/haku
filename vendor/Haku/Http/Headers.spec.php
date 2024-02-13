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
	beforeEach,
};

use Haku\Http\Headers;

spec('Http/Headers', function()
{

	describe('Standardized headers', function()
	{

		$headers = new Headers();

		beforeEach(function() use ($headers)
		{
			$headers->flush();
		});

		it('can add headers', function() use ($headers)
		{
			$headers->set('Accept-Language', 'sv_SE');

			return expect($headers->has('Accept-Language'))->toBeTrue();
		});

		it('can remove headers', function() use ($headers)
		{
			$headers->set('Accept-Language', 'sv_SE');
			$headers->remove('Accept-Language');

			return expect($headers->has('Accept-Language'))->toBeFalse();
		});

		it(
			'can set headers regardless of casing and hyphen omitted',
			function() use ($headers)
		{
			$headers->set('max forwards', '3');

			return expect($headers->getAll())->toHaveIndexedKey('Max-Forwards');
		});

		it('can add several headers', function() use ($headers)
		{
			$headers->append([
				'content type' => 'application/json',
				'content language' => 'en_US'
			]);

			return expectAll(
				expect($headers->getAll())->size()->toBeGreaterThanOrEqualTo(2),
				expect($headers->getAll())->toHaveIndexedKey('Content-Type'),
				expect($headers->getAll())->toHaveIndexedKey('Content-Language')
			);
		});

	});

});
