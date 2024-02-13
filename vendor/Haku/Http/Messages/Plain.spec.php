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

use Haku\Http\Messages\Plain;

spec('Http/Messages/Plain', function()
{

	describe('Plain text message', function()
	{

		it('can render', function()
		{
			$message = Plain::from('hello world');

			return expect($message->asRendered())->toEqual('hello world');
		});

		it('can validate', function()
		{
			$message = Plain::from('hello world');

			return expect($message->valid())->toBeTrue();
		});

	});

});
