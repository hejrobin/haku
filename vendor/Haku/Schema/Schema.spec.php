<?php
declare(strict_types=1);

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
};

use Haku\Schema\Schema;

spec('Haku/Schema/Schema', function()
{

	describe('Schema validation', function()
	{

		it('can validate a schema', function()
		{

			$schema = new Schema([
				'foo' => ['required', 'len: 3'],
				'bar' => ['len: 5..']
			]);

			$valid = $schema->validates([
				'foo' => 'Foo',
				'bar' => 'at least five characters'
			]);

			return expect($valid)->toBeTrue();

		});

	});

});
