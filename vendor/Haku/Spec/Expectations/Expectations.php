<?php
declare(strict_types=1);

namespace Haku\Spec\Expectations;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Http\{
	Status,
	Message,
};

use Haku\Spec\RouteExpectationResult;

use function Haku\Generic\Arrays\any;

final class Expectations implements ExpectationsInterface
{

	public function __construct(
		public readonly mixed $actual,
		private ?string $customReportMessage = null,
		private bool $expectInverseResult = false,
	) {}

	public static function from(
		mixed $actual,
		?string $customReportMessage = null,
		bool $expectInverseResult = false,
	): static
	{
		return new static($actual, $customReportMessage, $expectInverseResult);
	}

	private function report(
		string $reportMessage
	): string
	{
		return $this->customErrorMessage ?? $reportMessage;
	}

	/**
	 *	Sets custom error message to use in report
	 */
	public function reportAs(
		string $reportMessage
	): self
	{
		$this->customReportMessage = $reportMessage;

		return $this;
	}

	public function size(): static
	{
		if (is_countable($this->actual))
		{
			return static::from(count($this->actual));
		}
		else if (is_string($this->actual))
		{
			return static::from(strlen($this->actual));
		}
		else
		{
			throw new \TypeError('Expectation is not countable or string.');
		}
	}

	/**
	 *	Attempts to call function or class method.
	 */
	public function withArguments(
		array $arguments,
		?string $methodName = null,
	): static
	{
		try
		{
			$callback = $this->actual;

			if ($methodName)
			{
				$callback = [$callback, $methodName];
			}

			if (is_callable($callback) === false)
			{
				throw new \TypeError('Expectation is not callable.');
			}

			return static::from(
				call_user_func_array($callback, $arguments)
			);
		}
		catch (\Throwable $throwable)
		{
			return static::from($throwable);
		}
	}

	public function withoutArguments(
		?string $methodName = null,
	): static
	{
		return $this->withArguments([], $methodName);
	}

	public function call(
		?string $methodName = null,
		?array $arguments = []
	): static
	{
		return $this->withArguments($arguments, $methodName);
	}

	public function not(): static
	{
		return static::from(
			$this->actual,
			$this->customReportMessage,
			expectInverseResult: true,
		);
	}

	protected function invert(
		ExpectationResult $result,
		string $errorMessage
	): ExpectationResult {
		static::from(
			$this->actual,
			$this->customReportMessage,
			expectInverseResult: true,
		);

		return new ExpectationResult(
			$result->success === false,
			$errorMessage,
		);
	}

	private function toExpectationResult(
		bool $success,
		string $errorMessage,
		string $inverseErrorMessage,
		mixed $actual = null,
		mixed $expect = null,
	): ExpectationResult {
		if ($this->expectInverseResult)
		{
			return new ExpectationResult(
				$success === false,
				$inverseErrorMessage,
				actual: $expect,
				expect: $actual
			);
		}

		return new ExpectationResult(
			$success,
			$errorMessage,
			$actual,
			$expect
		);
	}

	public function toEqual(
		mixed $expected
	): ExpectationResult
	{
		return $this->toExpectationResult(
			success: $this->actual === $expected,
			errorMessage: 'expectation is not equal',
			inverseErrorMessage: 'expectation is equal',
			actual: $this->actual,
			expect: $expected,
		);
	}

	/**
	 *	Semantic alias for {@see Haku\Spec\Expectations::toEqual}
	 */
	public function toReturn(
		mixed $expected
	): ExpectationResult {
		return $this->toEqual($expected);
	}

	/**
	 *	Semantic alias for {@see Haku\Spec\Expectations::toEqual}
	 */
	public function toBe(
		mixed $expected
	): ExpectationResult {
		return $this->toEqual($expected);
	}

	public function toEqualAny(
		mixed ...$expectations
	): ExpectationResult
	{
		$actual = $this->actual;

		$equalsAny = any(
			$expectations,
			function(mixed $expectation) use ($actual)
			{
				return $expectation === $actual;
			}
		);

		return $this->toExpectationResult(
			success: $equalsAny === true,
			errorMessage: 'expectation not present in list',
			inverseErrorMessage: 'expectation present in list',
			actual: $this->actual,
			expect: $expectations,
		);
	}

	public function toBeTrue(): ExpectationResult
	{
		return $this->toExpectationResult(
			success: $this->actual === true,
			errorMessage: 'expectation is not explicitly true',
			inverseErrorMessage: 'expectation is explicitly true',
			actual: $this->actual,
			expect: true,
		);
	}

	public function toBeFalse(): ExpectationResult
	{
		return $this->toExpectationResult(
			success: $this->actual === false,
			errorMessage: 'expectation is not explicitly false',
			inverseErrorMessage: 'expectation is explicitly false',
			actual: $this->actual,
			expect: false,
		);
	}

	public function toBeInstanceOf(
		string $instance
	): ExpectationResult
	{
		return $this->toExpectationResult(
			$this->actual instanceof $instance,
			"expectation is not an instance of '$instance'",
			"expectation is an instance of '$instance'",
			actual: $this->actual,
			expect: $instance,
		);
	}

	public function toBeTypeOf(
		string $typeName
	): ExpectationResult
	{
		$validObjectTypes = [
			'array',
			'bool',
			'callable',
			'double',
			'float',
			'int',
			'integer',
			'long',
			'null',
			'numeric',
			'object',
			'real',
			'resource',
			'scalar',
			'string',
		];

		if (!in_array(strtolower($typeName), $validObjectTypes))
		{
			throw new \TypeError("$typeName is not a valid object type.");
		}

		return $this->toExpectationResult(
			success: call_user_func("is_$typeName", $this->actual),
			errorMessage: "expectation is not type of '$typeName'",
			inverseErrorMessage: "expectation is type of '$typeName'",
			actual: gettype($this->actual), // @todo Fix this to match $validObjectTypes
			expect: $typeName,
		);
	}

	/**
	 *	Fundamentally same as calling `withArguments([])->toBeInstanceOf(\Throwable::class)`.
	 */
	public function toThrow(
		string $exceptionType = '\Throwable'
	): ExpectationResult
	{
		$actual = $this->actual;

		if (is_object($actual))
		{
			$actual = get_class($actual);
		}

		$isValidException = $actual === $exceptionType;

		if (
			is_object($this->actual) &&
			$this->actual instanceof \Throwable &&
			$exceptionType === '\Throwable'
		) {
			$isValidException = true;
		}

		return $this->toExpectationResult(
			success: $isValidException,
			errorMessage: "expectation did not throw '$exceptionType' (got $actual)",
			inverseErrorMessage: "expectation threw '$exceptionType' (expected $exceptionType)",
			actual: $actual,
			expect: $exceptionType,
		);
	}

	/**
	 *	Validates presence of array index.
	 */
	public function toHaveIndexedKey(
		string | int $index,
	): ExpectationResult
	{
		if (!is_array($this->actual))
		{
			return new ExpectationResult(
				false,
				$this->report("expectation is not an array or array-like")
			);
		}

		$hasIndexedKey = array_key_exists($index, $this->actual);

		return $this->toExpectationResult(
			success: $hasIndexedKey,
			errorMessage: "expectation does not have indexed key '$index'",
			inverseErrorMessage: "expectation has indexed key '$index'"
		);
	}

	/**
	 *	Validates value of indexed array.
	 */
	public function toHaveIndexedValue(
		string | int $index,
		mixed $value,
	): ExpectationResult {
		$hasIndexedValue =
			array_key_exists($index, $this->actual) &&
			$this->actual[$index] === $value;

		return $this->toExpectationResult(
			success: $hasIndexedValue,
			errorMessage: "expectation does not have indexed value '$value' of key '$index'",
			inverseErrorMessage: "expectation has indexed value '$value' of key '$index'"
		);
	}

	public function toInclude(
		mixed ...$values
	): ExpectationResult
	{
		if (is_object($this->actual))
		{
			$actualValues = get_object_vars($this->actual);
		}
		else if (is_array($this->actual))
		{
			$actualValues = $this->actual;
		}
		else
		{
			throw new \TypeError('Expectation is not an array or object.');
		}

		$includes = array_filter(
			$actualValues,
			function(mixed $value) use ($values)
			{
				return in_array($value, $values);
			}
		);

		return $this->toExpectationResult(
			success: count($includes) > 0,
			errorMessage: "expectation does not include any of: " . implode(', ', $values),
			inverseErrorMessage: "expectation includes at least one of: " . implode(', ', $values),
		);
	}

	/**
	 *	Vaidates presence of a property in a class or instace.
	 */
	public function toHaveProperty(
		string $propertyName
	): ExpectationResult {
		return $this->toExpectationResult(
			success: property_exists($this->actual, $propertyName) === true,
			errorMessage: "expectation does not have property '$propertyName'",
			inverseErrorMessage: "expectation has property '$propertyName'"
		);
	}

	/**
	 *	Attempts to validate class or instance property value.
	 */
	public function toHavePropertyValue(
		string $propertyName,
		mixed $value
	): ExpectationResult
	{
		try
		{
			$propertyValue =
				property_exists($this->actual, $propertyName) ?
					$this->actual->{$propertyName} :
					null;

			return $this->toExpectationResult(
				success: $propertyValue === $value,
				errorMessage: "expectation does not have property value '$value' of key '$propertyName'",
				inverseErrorMessage: "expectation has property value '$value' of key '$propertyName'"
			);
		}
		catch (\Throwable $throwable)
		{
			return new ExpectationResult(
				false,
				$this->report("could not get property value from expected")
			);
		}
	}

	public function toBeLessThan(
		int | float $max
	): ExpectationResult
	{
		return $this->toExpectationResult(
			success: $this->actual < $max,
			errorMessage: "expectation is not less than $max",
			inverseErrorMessage: "expectation is less than $max"
		);
	}

	public function toBeLessThanOrEqualTo(
		int | float $max
	): ExpectationResult
	{
		return $this->toExpectationResult(
			success: $this->actual <= $max,
			errorMessage: "expectation is not less than or equal to $max",
			inverseErrorMessage: "expectation is less than or equal to $max"
		);
	}

	public function toBeGreaterThan(
		int | float $min
	): ExpectationResult
	{
		return $this->toExpectationResult(
			success: $this->actual > $min,
			errorMessage: "expectation is not greater than $min",
			inverseErrorMessage: "expectation is greater than $min"
		);
	}

	public function toBeGreaterThanOrEqualTo(
		int | float $min
	): ExpectationResult
	{
		return $this->toExpectationResult(
			success: $this->actual >= $min,
			errorMessage: "expectation is not greater than or equal to $min",
			inverseErrorMessage: "expectation is greater than or equal to $min"
		);
	}

	public function toBeWithinRange(
		int | float $min,
		int | float $max
	): ExpectationResult
	{
		return $this->toExpectationResult(
			success: $this->actual >= $min && $this->actual <= $max,
			errorMessage: "expectation is not within range $min:$max",
			inverseErrorMessage: "expectation is within range $min:$max",
		);
	}

	public function toBeOutsideRange(
		int | float $min,
		int | float $max
	): ExpectationResult
	{
		return $this->toExpectationResult(
			success: $this->actual < $min || $this->actual > $max,
			errorMessage: "expectation is not outside range $min:$max",
			inverseErrorMessage: "expectation is outside range $min:$max",
		);
	}

	public function toRespondWith(
		string $messageClassName,
		Status $httpStatus = Status::OK,
	): ExpectationResult
	{
		$actual = $this->actual;

		if ($actual instanceof RouteExpectationResult)
		{
			$validMessage = get_class($actual->response) === $messageClassName;
			$validStatus = $actual->status === $httpStatus;

			$formatErrorMessage = function (string $errorMessage) use ($messageClassName, $httpStatus) {
				return sprintf("{$errorMessage} %s, %s", $messageClassName, "Haku\\Http\\Status::{$httpStatus->name}");
			};

			return $this->toExpectationResult(
				success: $validMessage && $validStatus,
				errorMessage: $formatErrorMessage("expectation response is not"),
				inverseErrorMessage: $formatErrorMessage("expectation response is"),
			);
		}

		return $this->toExpectationResult(
			success: false,
			errorMessage: "expectation is not result from route()",
			inverseErrorMessage: "expectation is result from route()",
		);
	}

}
