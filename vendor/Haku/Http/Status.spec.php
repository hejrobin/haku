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

use Haku\Http\Status;

spec('Http/Status', function()
{

	describe('Standard status codes', function()
	{

		it('recognizes a success status', function()
		{
			return expect(Status::from(200))->toBe(Status::OK);
		});

		it('recognizes a redirect status', function()
		{
			return expect(Status::from(307))->toBe(Status::TemporaryRedirect);
		});

	});

	describe('Non-standard status codes', function()
	{

		it('recognizes a funny HTTP status code', function()
		{
			return expectAll(
				expect(Status::from(732))->toBe(Status::FuckingUnicode),
				expect(Status::from(732)->getName())->toEqual('Fucking Unic💩de'),
			);
		});

	});

});
