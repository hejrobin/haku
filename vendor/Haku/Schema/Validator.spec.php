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

use Haku\Schema\Validator;

spec('Haku/Schema/Validator', function()
{

	describe('Validator', function()
	{

		$validator = new Validator();

		$schema = [
			'foo' => 'Foo',
			'bar' => 'Bar',
			'baz' => 'Baz',
			'lorem' => 'Foo',
			'ipsum' => 'Pomme',
			'email' => 'harley.quinn@arkham.org',
			'password' => 'Sup3R!$ekR3t!',
			'time' => 1234567890,
		];

		it('validates required field', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('required', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates required with field', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('requiredWith: bar', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates omitted field', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('omitted', 'bax', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates field equality', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('eq: lorem', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates exact field length', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('len: 3', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates greater than or equal field length', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('len: 3..', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates lower than or equal field length', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('len: ..3', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates field length within a specific range', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('len: 1..5', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates field is enum and is correct value', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('enum: Foo, Bar, Baz', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates field value matches regex', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('regex: \w+', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates field value matches regex', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('regex: \w+', 'foo', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates field as a valid email address', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('emailAddress', 'email', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates field as a strong password', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('strongPassword', 'password', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates field as a unix timestamp', function () use ($validator, $schema)
		{
			$result = $validator->validateOne('unixTimestamp', 'time', $schema);

			return expect($result->success)
				->reportAs($result->error)
				->toBeTrue();
		});

		it('validates several rules', function () use ($validator, $schema)
		{
			$result = $validator->validateAll(['regex: \w+', 'requiredWith: bar', 'len: 2..32'], 'foo', $schema);

			return expectAll(
				expect($result->success)
					->reportAs('validator failed')
					->toBeTrue(),
				expect(count($result->errors))
					->reportAs(implode(', ', $result->errors))
					->toEqual(0),
			);
		});

	});

});
