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

use Haku\Http\Method;

spec('Http/Method', function()
{

	describe('With payload', function()
	{

		it('allows a payload for PUT, POST and PATCH', function()
		{
			return expectAll(
				expect(Method::Put->allowsPayload())->toBeTrue(),
				expect(Method::Post->allowsPayload())->toBeTrue(),
				expect(Method::Patch->allowsPayload())->toBeTrue(),
			);
		});

		it('does not allow payload for other methods', function()
		{
			return expectAll(
				expect(Method::Head->allowsPayload())->toBeFalse(),
				expect(Method::Options->allowsPayload())->toBeFalse(),
				expect(Method::Get->allowsPayload())->toBeFalse(),
				expect(Method::Trace->allowsPayload())->toBeFalse(),
				expect(Method::Connect->allowsPayload())->toBeFalse(),
			);
		});

	});

});
