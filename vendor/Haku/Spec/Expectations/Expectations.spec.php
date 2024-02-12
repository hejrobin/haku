<?php
declare(strict_types=1);

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
	expectAll,
};

spec('Spec\Expectations', function() {

	describe('Equality checks', function() {

		it('expects string equality', fn() => expect('foo')->toEqual('foo'));

		it('expects number equality', fn() => expect(42)->toBe(42));

		it('expects any of foo, bar, 42', fn() => expect(42)->toEqualAny('foo', 'bar', 42));

		it('expects explicit true', fn() => expect('foo' === 'foo')->toBeTrue());

		it('expects explicit false', fn() => expect('foo' !== 'foo')->toBeFalse());

		it('can validate instances', function() {
			$foo = new \stdClass;

			return expectAll(
				expect($foo)->toBeInstanceOf(\stdClass::class),
				expect($foo)->toBeInstanceOf('\stdClass'),
			);
		});

		it('can validate object types', fn() => expectAll(
			expect(666)->toBeTypeOf('int'),
			expect('foo')->toBeTypeOf('string'),
			expect([])->toBeTypeOf('array'),
			expect((object)[])->toBeTypeOf('object'),
		));

		it('throws an exception if callable signature fails', function () {
			$willThrowDivisionByZero = fn() => 0 / 0;

			return expectAll(
				expect($willThrowDivisionByZero)->toThrow(),
				expect($willThrowDivisionByZero)->toThrow(\DivisionByZeroError::class),
			);
		});

		it('validates presence of an array index', function() {
			$data = [
				1337 => 'l33t!',
				'yeet' => 'y33t!'
			];

			return expectAll(
				expect($data)->toHaveIndexedKey(1337),
				expect($data)->toHaveIndexedKey('yeet'),
			);
		});

		it('validates presence of an indexed value', function() {
			$data = [
				'yeet' => 'y33t!'
			];

			return expect($data)->toHaveIndexedValue('yeet', 'y33t!');
		});

		it('validates value presence in an object or array', function() {
			$arr = ['Dinglehopper'];

			$obj = (object) [
				'fork' => 'Dinglehopper'
			];

			return expectAll(
				expect($arr)->toInclude('Dinglehopper'),
				expect($obj)->toInclude('Dinglehopper'),
			);
		});

		it('validate class property presence', function() {
			$obj = new \stdClass();
			$obj->hello = 'Hello World';

			return expect($obj)->toHaveProperty('hello');
		});

		it('validates class property value', function() {
			$obj = new \stdClass();
			$obj->hello = 'Hello World';

			return expect($obj)->toHavePropertyValue('hello', 'Hello World');
		});

	});

	describe('Inequality checks', function() {

		it('expects string equality', fn() => expect('foo')->not()->toEqual('bar'));

		it('expects number equality', fn() => expect(42)->not()->toBe(13));

		it('expects any of foo, bar, 42', fn() => expect('baz')->not()->toEqualAny('foo', 'bar', 42));

		it('expects explicit true', fn() => expect('foo' === 'bar')->not()->toBeTrue());

		it('expects explicit false', fn() => expect('baz' === 'baz')->not()->toBeFalse());

		it('can validate instances', function() {
			$foo = new \stdClass;

			return expectAll(
				expect($foo)->not()->toBeInstanceOf(\WeakMap::class),
				expect($foo)->not()->toBeInstanceOf('\WeakMap'),
			);
		});

		it('can validate object types', fn() => expectAll(
			expect('666')->not()->toBeTypeOf('int'),
			expect(1337)->not()->toBeTypeOf('string'),
			expect((object)[])->not()->toBeTypeOf('array'),
			expect([])->not()->toBeTypeOf('object'),
		));

		it('throws an exception if callable signature fails', function () {
			$willNotThrowDivisionByZero = fn() => 16 / 9;

			return expectAll(
				expect($willNotThrowDivisionByZero)->not()->toThrow(),
				expect($willNotThrowDivisionByZero)->not()->toThrow(\DivisionByZeroError::class),
			);
		});

		it('validates presence of an array index', function() {
			$data = [
				9001 => 'Over nine thousand!',
				'leet' => 'Whoop!'
			];

			return expectAll(
				expect($data)->not()->toHaveIndexedKey(1337),
				expect($data)->not()->toHaveIndexedKey('yeet'),
			);
		});

		it('validates presence of an indexed value', function() {
			$data = [
				'yeet' => 'YEET!'
			];

			return expect($data)->not()->toHaveIndexedValue('yeet', 'y33t!');
		});

		it('validates value presence in an object or array', function() {
			$arr = ['Spoon'];

			$obj = (object) [
				'fork' => 'Spoon'
			];

			return expectAll(
				expect($arr)->not()->toInclude('Dinglehopper'),
				expect($obj)->not()->toInclude('Dinglehopper'),
			);
		});

		it('validate class property presence', function() {
			$obj = new \stdClass();
			$obj->tjena = 'Hello World';

			return expect($obj)->not()->toHaveProperty('hello');
		});

		it('validates class property value', function() {
			$obj = new \stdClass();
			$obj->tjena = 'Hejsan världen';

			return expect($obj)->not()->toHavePropertyValue('hello', 'Hello World');
		});

	});

	describe('Numerical checks', function() {

		it('expects less than', fn() => expect(13)->toBeLessThan(42));

		it('expects less than, or equal to', fn() => expect(42)->toBeLessThanOrEqualTo(42));

		it('expects greater than', fn() => expect(1337)->toBeGreaterThan(42));

		it('expects greater than, or equal to', fn() => expect(9000)->toBeGreaterThanOrEqualTo(42));

		it('expects to be between a specific range', fn() => expect(13)->toBeWithinRange(1, 99));

		it('expects to be outside a specific range', fn() => expectAll(
			expect(5)->toBeOutsideRange(13, 99),
			expect(100)->toBeOutsideRange(13, 99)
		));

	});

	describe('Inverse numerical checks', function() {

		it('expects not less than', fn() => expect(42)->not()->toBeLessThan(13));

		it('expects not less than, or equal to', fn() => expect(42)->not()->toBeLessThanOrEqualTo(40));

		it('expects not greater than', fn() => expect(1337)->not()->toBeGreaterThan(9000));

		it('expects not greater than, or equal to', fn() => expect(9000)->not()->toBeGreaterThanOrEqualTo(9001));

		it('expects not to be between a specific range', fn() => expect(256)->not()->toBeWithinRange(1, 99));

		it('expects not to be outside a specific range', fn() => expectAll(
			expect(42)->not()->toBeOutsideRange(13, 99),
			expect(98)->not()->toBeOutsideRange(13, 99)
		));

	});

});
