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

use Haku\Http\Messages\Json;

spec('Http/Messages/Json', function()
{

	describe('A JSON message', function()
	{

		it('can render', function()
		{
			$message = Json::from([
				'data' => 'hello world'
			]);

			$json = json_encode(
				['data' => 'hello world'],
				\JSON_PRETTY_PRINT | \JSON_NUMERIC_CHECK
			);

			return expectAll(
				expect($message)->call('get', ['data'])->toReturn('hello world'),
				expect($message->asRendered())->toEqual($json),
			);
		});

		it('can validate', function()
		{
			$message = Json::from([
				'data' => 'hello world'
			]);

			return expect($message->valid())->toBeTrue();
		});

	});

});
