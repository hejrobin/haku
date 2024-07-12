<?php
declare(strict_types=1);

namespace Haku\Schema;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Schema\Exceptions\ValidationException;

class Validator
{

	/**
	 *	Expects a field to be present in record.
	 *
	 *	@pattern "required"
	 */
	protected function validateRequired(string $field, array $record): ValidationResult
	{
		$isPresent = array_key_exists($field, $record);
		$error = sprintf("'%s': is required, but is not present in record", $field);

		return new ValidationResult($isPresent, $error);
	}

	/**
	 *	Expects two fields to be present in record.
	 *
	 *	@pattern "requiredWith: compareField"
	 */
	protected function validateRequiredWith(string $field, array $record, string $compareField): ValidationResult
	{
		$existsWith =
			$this->validateRequired($field, $record)->success && $this->validateRequired($compareField, $record)->success;

		$error = sprintf("'%s': is required with '%s'.", $compareField, $field);

		return new ValidationResult($existsWith, $error);
	}

	/**
	 *	Inverse of {@see Haku\Schema\Validation::validateRequired}
	 *
	 *	@pattern "omitted"
	 */
	protected function validateOmitted(string $field, array $record): ValidationResult
	{
		$isOmitted = array_key_exists($field, $record) === false;
		$error = sprintf("'%s': expected field to be omitted from record", $field);

		return new ValidationResult($isOmitted, $error);
	}

	/**
	 *	Expects two fields to exactly match.
	 *
	 *	@pattern "eq: compareField"
	 */
	protected function validateEq(string $field, array $record, string $compareField): ValidationResult
	{
		$matches = !empty($record[$field]) && !empty($record[$compareField]) && $record[$field] === $record[$compareField];
		$error = sprintf('\'%1$s\': values of \'%1$s\' and \'%2$s\' do not match', $field, $compareField);

		return new ValidationResult($matches, $error);
	}

	/**
	 *	Validates field value length.
	 *
	 *	@pattern
	 *		len: 123 		- Matches exact length
	 *		len: 10..		- Matches greater than, or equal to
	 *		len: ..10		- Matches lower than, or equal to
	 *		len: 1..10	- Matches length between
	 */
	protected function validateLen(string $field, array $record, string $range): ValidationResult
	{
		$validLength = false;
		$error = '';

		if (preg_match('/(?P<min>(?:\d+)?)(?P<dots>(?:\.{2})?)(?P<max>(?:\d+)?)/', $range, $matches))
		{
			extract($matches);

			$hasMin = $min !== '';
			$hasMax = $max !== '';

			$min = intval($min);
			$max = intval($max);

			$expectsExactLength = $hasMin && !$hasMax && !$dots;
			$expectsRangeBetween = $hasMin && $hasMax && $dots;
			$expectsRangeFrom = $hasMin && !$hasMax && $dots;
			$expectsRangeTo = !$hasMin && $hasMax && $dots;

			if ($max <= $min)
			{
				$error = sprintf("'%s': max cannot be lower than min", $field);
			}

			if (empty($record[$field]))
			{
				$error = sprintf("'%s': is empty or not set", $field);
			}

			$fieldValueLength = strlen($record[$field] ?? '');

			if ($expectsExactLength)
			{
				$validLength = $fieldValueLength === $min;

				$error = sprintf("'%s': invalid length, expected %d, got: %d", $field, $min, $fieldValueLength);
			}
			elseif ($expectsRangeBetween)
			{
				$validLength = $fieldValueLength >= $min && $fieldValueLength <= $max;

				$error = sprintf(
					"'%s': invalid length, expected between %d and %d, got: %d",
					$field,
					$min,
					$max,
					$fieldValueLength,
				);
			}
			elseif ($expectsRangeFrom)
			{
				$validLength = $fieldValueLength >= $min;

				$error = sprintf(
					"'%s': invalid length, expected longer than or matching %d, got %d",
					$field,
					$min,
					$fieldValueLength,
				);
			}
			elseif ($expectsRangeTo)
			{
				$validLength = $fieldValueLength <= $max;

				$error = sprintf(
					"'%s': invalid length, expected shorter than or matching %d, got %d",
					$field,
					$max,
					$fieldValueLength,
				);
			}
		}

		return new ValidationResult($validLength, $error);
	}

	/**
	 *	Validates if field is boolean value.
	 *
	 *	@param "bool"
	 */
	protected function validateBool(string $field, array $record): ValidationResult
	{
		$isBoolean = false;

		if (!empty($record[$field]))
		{
			$isBoolean =
				is_bool($record[$field]) ||
				$record[$field] === 'true' ||
				$record[$field] === 'false' ||
				$record[$field] === '1' ||
				$record[$field] === '0';
		}

		$error = sprintf("'%s': is not a boolean", $field);

		return new ValidationResult($isBoolean, $error);
	}

	/**
	 *	Validates if field value is from an enum.
	 *
	 *	@pattern "enum: foo, bar, baz"
	 */
	protected function validateEnum(string $field, array $record, string $enums): ValidationResult
	{
		$enums = array_map('trim', explode(',', $enums));
		$existsInEnum = false;

		if (!empty($record[$field]))
		{
			$existsInEnum = in_array($record[$field], $enums);
		}

		$error = sprintf(
			"'%s': \"%s\" does not exist in enum, expected: %s",
			$field,
			$record[$field] ?? 'undefined',
			implode(', ', $enums),
		);

		return new ValidationResult($existsInEnum, $error);
	}

	/**
	 *	Attempts to validate field value with regular expression.
	 *
	 *	@pattern "regex: (\w+)"
	 */
	protected function validateRegex(string $field, array $record, string $regex): ValidationResult
	{
		$matches = false;
		$regex = '/' . $regex . '/';

		$error = sprintf("'%s': regex validation failed", $field);

		if (!empty($record[$field]))
		{
			$matches = preg_match($regex, $record[$field]) === 1;

			$error = sprintf("'%s': regex validation failed, tested \"%s\" against %s", $field, $record[$field], $regex);
		}

		return new ValidationResult($matches, $error);
	}

	/**
	 *	Validates that a field is a RFC 822 addr compliant email address.
	 */
	protected function validateEmailAddress(string $field, array $record): ValidationResult
	{
		$isValid = false;

		$error = sprintf("'%s': \"%s\" is not a valid email", $field, $record[$field] ?? '');

		if (!empty($record[$field]))
		{
			$isValid = filter_var($record[$field], FILTER_VALIDATE_EMAIL) !== false;
		}

		return new ValidationResult($isValid, $error);
	}

	/**
	 *	Validates field as a "strong" password, lower and upper case chars, at least one special char or number, and at least 8 characters long.
	 */
	protected function validateStrongPassword(string $field, array $record): ValidationResult
	{
		$isStrongPassword = $this->validateRegex($field, $record, '^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$');
		$error = sprintf("'%s': is not a strong password", $field);

		return new ValidationResult($isStrongPassword->success, $isStrongPassword->error);
	}

	/**
	 *	Validates field as a UNIX timestamp.
	 */
	protected function validateUnixTimestamp(string $field, array $record): ValidationResult
	{
		$isValid = false;

		$error = sprintf("'%s': is not a valid UNIX timestamp", $field);

		$timestamp = $record[$field] ?? '';

		$isValid = filter_var($timestamp, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 0,
				'max_range' => 2147483647,
			],
		]) !== false;

		if (!$isValid)
		{
			$isValid = (string) (int) $timestamp === $timestamp && $timestamp <= PHP_INT_MAX && $timestamp >= ~PHP_INT_MAX;
		}

		return new ValidationResult($isValid, $error);
	}

	/**
	 *	Parses a rule, whether or not it has arguments. e.g. "foo: Foo"
	 */
	protected function parseRule(string $rule): array
	{
		$parts = explode(':', str_replace(' ', '', $rule));

		return [
			'rule' => $parts[0] ?? '',
			'argument' => count($parts) > 1 ? $parts[1] : null,
		];
	}

	/**
	 *	Validates a single field rule.
	 */
	public function validateOne(string $rule, string $field, array $record): ValidationResult
	{
		$parsed = $this->parseRule($rule);
		$validatorCallback = 'validate' . ucfirst($parsed['rule']);

		if (!method_exists($this, $validatorCallback))
		{
			throw new ValidationException(sprintf('Invalid validator: %s', $validatorCallback));
		}
		else
		{
			return call_user_func_array([$this, $validatorCallback], [$field, $record, $parsed['argument']]);
		}
	}

	/**
	 *	Validates each rule in input string.
	 */
	public function validateAll(array $rules, string $field, array $record): ValidationResultSet
	{
		$errors = [];

		$isOptional = in_array('optional', $rules);

		if ($isOptional)
		{
			$rules = array_filter($rules, function (string $rule) {
				return $rule !== 'optional';
			});
		}

		foreach ($rules as $rule)
		{
			$shouldValidate = true;

			if ($isOptional && empty($record[$field]))
			{
				$shouldValidate = false;
			}

			if (!empty($rule) && $shouldValidate)
			{
				$result = $this->validateOne($rule, $field, $record);

				if ($result->success === false)
				{
					$errors[] = $result->error;
				}
			}
		}

		$success = count($errors) === 0;

		return new ValidationResultSet($success, $errors);
	}

}
