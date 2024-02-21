<?php
declare(strict_types=1);

namespace Haku\Spec\Expectations;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Http\{
	Status,
	Message,
};

interface ExpectationsInterface
{

	/**
	 *	Must get size of $actual and return a new instance with size as the expectation value ($actual).
	 */
	public function size(): static;

	/**
	 *	Must invoke $actual if it is callable, with arguments.
	 */
	public function withArguments(
		array $arguments,
		?string $methodName
	): static;

	/**
	 *	Must invoke $actual if it is callable, without arguments.
	 */
	public function withoutArguments(
		?string $methodName
	): static;

	/**
	 *	Inverse argument alias of {@see Haku\Spec\Expectations\withArguments}
	 */
	public function call(
		?string $methodName,
		?array $arguments,
	): static;

	/**
	 *	Expect actual to be exact match to expected.
	 */
	public function toEqual(
		mixed $expected
	): ExpectationResult;

	/**
	 *	Alias for toEqual, used for semantics when invoking methods via withArguments, withoutArguments or call.
	 */
	public function toReturn(
		mixed $expected
	): ExpectationResult;

	/**
	 *	Expect actual to match at least one expectation.
	 */
	public function toEqualAny(
		mixed ...$expectations
	): ExpectationResult;

	/**
	 *	Expect actual to be explicitly true.
	 */
	public function toBeTrue(): ExpectationResult;

	/**
	 *	Expect actual to be explicitly false.
	 */
	public function toBeFalse(): ExpectationResult;

	/**
	 *	Expects actual to be instance of expected.
	 */
	public function toBeInstanceOf(
		string $instance
	): ExpectationResult;

	/**
	 *	Expects actual to be type of expected.
	 */
	public function toBeTypeOf(
		string $typeName
	): ExpectationResult;

	/**
	 *	Expects actual to be instance of a throwable.
	 */
	public function toThrow(
		string $exceptionType = '\Throwable'
	): ExpectationResult;

	/**
	 *	Expects actual to be an array with key
	 */
	public function toHaveIndexedKey(
		string | int $index
	): ExpectationResult;

	/**
	 *	Expects actual to be an array with key
	 */
	public function toHaveIndexedValue(
		string | int $index,
		mixed $value,
	): ExpectationResult;

	/**
	 *	Expects actual to be a an array that includes one or more items.
	 */
	public function toInclude(
		mixed ...$values
	): ExpectationResult;

	/**
	 *	Expects actual to be a class or object with property.
	 */
	public function toHaveProperty(
		string $propertyName
	): ExpectationResult;

	/**
	 *	Expects actual to be a class or object with property.
	 */
	public function toHavePropertyValue(
		string $propertyName,
		mixed $value,
	): ExpectationResult;

	public function toBeLessThan(
		int | float $max
	): ExpectationResult;

	public function toBeLessThanOrEqualTo(
		int | float $max
	): ExpectationResult;

	public function toBeGreaterThan(
		int | float $min
	): ExpectationResult;

	public function toBeGreaterThanOrEqualTo(
		int | float $min
	): ExpectationResult;

	public function toBeWithinRange(
		int | float $min,
		int | float $max
	): ExpectationResult;

	public function toBeOutsideRange(
		int | float $min,
		int | float $max
	): ExpectationResult;

	public function toRespondWith(
		string $messageClassName,
		Status $httpStatus,
	): ExpectationResult;

}
