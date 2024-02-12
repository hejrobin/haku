<?php
declare(strict_types=1);

use Haku\Core\Factory;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
	expectAll,
};

class MockFactory
{
	use Factory;
}

class MockClass {
	public function helloWorld(): string
	{
		return 'Hello World!';
	}
}

spec('Core\Factory', function()
{

	describe('create instances', function()
	{

		it('can create new instance', function()
		{
			$factory = new MockFactory();
			$factory->initialize('MockClass');

			return expect($factory->has('mockClass'))->toBeTrue();
		});

		it('can create new instance with custom name', function()
		{
			$factory = new MockFactory();
			$factory->initialize('MockClass', 'testClass');

			return expectAll(
				expect($factory->has('mockClass'))->toBeFalse(),
				expect($factory->has('testClass'))->toBeTrue()
			);
		});

	});

	describe('stored instances', function() {

		it('can call stored instance', function() {
			$factory = new MockFactory();
			$factory->initialize('MockClass');

			return expect($factory->get('mockClass'))
				->call('helloWorld')
				->toReturn('Hello World!');
		});

	});

});
