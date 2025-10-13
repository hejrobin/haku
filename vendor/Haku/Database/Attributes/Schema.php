<?php
declare(strict_types=1);

namespace Haku\Database\Attributes;

use InvalidArgumentException;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use \Attribute;

/**
 *	Schema attribute for overriding property SQL definition in migrations
 *	Example: #[Schema('varchar)]
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Schema
{

	public readonly string $type;

	public readonly ?int $length;
	public readonly ?int $scale;
	public readonly ?int $precision;

	public readonly mixed $default;

	private readonly array $allowedFunctions;

	public function __construct(
		string $type,

		?int $length = null,
		?int $scale = null,
		?int $precision = null,

		mixed $default = null,
	) {
		$this->type = mb_strtolower($type);

		$this->length = $length;
		$this->scale = $scale;
		$this->precision = $precision;

		$this->allowedFunctions = [
			'NOW()',
			'CURRENT_TIMESTAMP',
			'UUID()',
			'NULL'
		];

		if ($default !== null)
		{
			$this->default = $this->sanitize($default);
		}
		else
		{
			$this->default = $default;
		}
	}

	private function sanitize(mixed $value): mixed
	{
		if (
			 is_string($value) &&
			 in_array(strtoupper($value), $this->allowedFunctions, true)
		) {
			return strtoupper($value);
		}

		return match($this->type) {
			'int', 'integer', 'smallint', 'mediumint', 'bigint' =>
				$this->validateInteger($value),

			'float', 'double', 'decimal', 'numeric' =>
				$this->validateFloat($value),

			'char', 'varchar', 'text' =>
				$this->validateString($value),

			 'timestamp', 'datetime' =>
					$this->validateTimestamp($value),

			default => throw new InvalidArgumentException("Unsupported column type: {$this->type}"),
		};
	}

	private function validateInteger(mixed $value): int
	{
		if (!is_int($value) && !ctype_digit((string) $value))
		{
			throw new InvalidArgumentException("Invalid default for int column");
		}

		return (int) $value;
	}

	private function validateFloat(mixed $value): float
	{
		if (!is_numeric($value))
		{
			throw new InvalidArgumentException("Invalid default for float or decimal column");
		}

		return (float) $value;
	}

	private function validateString(mixed $value): string
	{
		if (!is_string($value))
		{
			throw new InvalidArgumentException("Default must be a string");
		}

		if (preg_match('/(;|--|#|\/\*|\*\/|\\\\)/', $value))
		{
			throw new InvalidArgumentException("Unsafe characters or SQL delimiters in default string value");
		}

		return $value;
	}

	private function validateTimestamp(mixed $value): string
	{
		if (!preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', (string) $value))
		{
			throw new InvalidArgumentException("Invalid datetime default: $value");
		}

		return (string) $value;
	}

	private function sanitizeDefault(mixed $value): string
	{
		if (
			is_string($value) &&
			preg_match('/^[A-Z_]+\(\)$|^NULL$/', strtoupper($value))
		) {
			return $value;
		}

		if (is_string($value))
		{
			return "'" . str_replace("'", "''", $value) . "'";
		}

		if (is_float($value) && (int) $value === 0)
		{
			// Make sure we get default value *with* precision
			return sprintf('0.%s', str_pad('', $this->scale ?? 1, '0'));
		}

		if (is_int($value) || is_float($value))
		{
			return (string) $value;
		}

		throw new InvalidArgumentException("Cannot sanitize default value");
	}

	public function toSQL(): string
	{
		$sql = mb_strtoupper($this->type);

		$needsPrecision = in_array($this->type, ['decimal', 'numeric']);
		$needsLength = in_array($this->type, ['char', 'varchar']);

		if ($needsPrecision)
		{
			if (!$this->precision)
			{
				throw new InvalidArgumentException("Type {$this->type} needs precision");
			}

			$defs = [$this->precision];

			if ($this->scale !== null && $this->scale > 0)
			{
				$defs[] = $this->scale;
			}

			$sql .= '(' . implode(', ', $defs) . ')';
		}
		elseif ($needsLength && $this->length !== null)
		{
			$sql .= "({$this->length})";
		}

		if ($this->default !== null)
		{
			$sql .= ' DEFAULT ';
			$sql .= $this->sanitizeDefault($this->default);
		}

		return $sql;
	}

}
