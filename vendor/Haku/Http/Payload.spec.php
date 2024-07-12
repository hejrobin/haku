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

use function Haku\Spec\Mocking\{
	mockJsonPayload,
	getMockedJsonPayload
};

use Haku\Http\Payload;

spec('Http/Payload', function()
{

	describe('Validation', function()
	{

		it('can have confirmation errors', function()
		{
			mockJsonPayload([
				'username' => 'hejrobin'
			]);

			$createPayload = new Payload([
				'username' => ['len:3..32'],
				'password' => ['required', 'strongPassword'],
				'passwordConfirmation' => ['requiredWith: password', 'eq: password'],
			]);

			$numErrors = count($createPayload->errors());

			return expect($numErrors)->toBeGreaterThan(0);
		});

		it('can successfully validates', function()
		{
			mockJsonPayload([
				'username' => 'hejrobin',
				'password' => 'S0mePa$$w0rd!',
				'passwordConfirmation' => 'S0mePa$$w0rd!'
			]);

			$createPayload = new Payload([
				'username' => ['len:3..32'],
				'password' => ['required', 'strongPassword'],
				'passwordConfirmation' => ['requiredWith: password', 'eq: password'],
			]);

			$numErrors = count($createPayload->errors());

			return expect($numErrors)->toEqual(0);
		});

	});

});
