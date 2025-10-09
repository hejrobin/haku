<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use ReflectionClass;
use ReflectionProperty;

use Haku\Database\Attributes\{
	Entity,
	PrimaryKey,
	Schema,
	Timestamp,
	Validates,
};

use function Haku\Generic\Strings\snakeCaseFromCamelCase;

/**
 *	Simple schema generator that analyzes model attributes to generate a migration.
 */
class SchemaGenerator
{

	public static function generateFromModel(string $modelClass): string
	{
		$reflection = new ReflectionClass($modelClass);
		$entityAttributes = $reflection->getAttributes(Entity::class);

		if (empty($entityAttributes))
		{
			throw new \Exception("Model {$modelClass} must have #[Entity] attribute");
		}

		$entity = $entityAttributes[0]->newInstance();
		$tableName = $entity->tableName;

		$properties = $reflection->getProperties(
			ReflectionProperty::IS_PUBLIC |
			ReflectionProperty::IS_PROTECTED |
			ReflectionProperty::IS_PRIVATE
		);

		$columns = [];
		$primaryKeys = [];

		foreach ($properties as $property)
		{
			if ($property->getDeclaringClass()->getName() !== $modelClass)
			{
				continue;
			}

			$columnDef = self::generateColumnDefinition($property);

			if ($columnDef !== null)
			{
				$columns[] = $columnDef;
			}

			$pkAttributes = $property->getAttributes(PrimaryKey::class);

			if (!empty($pkAttributes))
			{
				$primaryKeys[] = snakeCaseFromCamelCase($property->getName());
			}
		}

		// Build SQL
		$sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (\n";
		$sql .= "  " . implode(",\n  ", $columns);

		if (!empty($primaryKeys))
		{
			$pkColumns = implode('`, `', $primaryKeys);
			$sql .= ",\n  PRIMARY KEY (`{$pkColumns}`)";
		}

		$sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

		return $sql;
	}

	/**
	 *	Generate column definition for a property
	 */
	private static function generateColumnDefinition(ReflectionProperty $property): ?string
	{
		$columnName = snakeCaseFromCamelCase($property->getName());

		$schemaAttributes = $property->getAttributes(Schema::class);

		if (!empty($schemaAttributes))
		{
			$schema = $schemaAttributes[0]->newInstance();

			return "`{$columnName}` {$schema->definition}";
		}

		// Get property type
		$type = $property->getType();

		if ($type === null)
		{
			return null;
		}

		$typeName = $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed';
		$nullable = $type->allowsNull();

		$isPrimaryKey = !empty($property->getAttributes(PrimaryKey::class));

		$timestampAttributes = $property->getAttributes(Timestamp::class);
		$isTimestamp = !empty($timestampAttributes);
		$timestampDefault = false;

		if ($isTimestamp)
		{
			$timestamp = $timestampAttributes[0]->newInstance();
			$timestampDefault = $timestamp->default;
		}

		$maxLength = null;
		$enumValues = null;

		$validatesAttributes = $property->getAttributes(Validates::class);

		if (!empty($validatesAttributes))
		{
			$validates = $validatesAttributes[0]->newInstance();
			$maxLength = self::extractMaxLengthFromValidates($validates);
			$enumValues = self::extractEnumValuesFromValidates($validates);
		}

		$sqlType = self::toSqlType($typeName, $isPrimaryKey, $isTimestamp, $timestampDefault, $maxLength, $enumValues);

		$definition = "`{$columnName}` {$sqlType}";

		if (
			!$nullable &&
			!($isPrimaryKey &&
			strpos($sqlType, 'AUTO_INCREMENT') !== false) &&
			!$isTimestamp
		) {
			$definition .= ' NOT NULL';
		}

		if ($nullable && !$isPrimaryKey && !$isTimestamp)
		{
			$definition .= ' DEFAULT NULL';
		}

		return $definition;
	}

	/**
	 *	Extract max length from Validates attribute len: constraint
	 */
	private static function extractMaxLengthFromValidates(Validates $validates): ?int
	{
		$rules = array_filter([
			$validates->onCreate,
			$validates->onUpdate,
		]);

		foreach ($rules as $ruleString)
		{
			if ($ruleString === null)
			{
				continue;
			}

			// Parse comma-separated rules
			$parts = array_map('trim', explode(',', $ruleString));

			foreach ($parts as $rule)
			{
				// Match len: patterns (e.g., "len: ..123", "len: 5", "len: 1..10")
				if (preg_match('/^len:\s*(?P<min>\d+)?(?P<dots>\.\.)?(?P<max>\d+)?$/', $rule, $matches))
				{
					// Extract max length from different patterns
					if (!empty($matches['max']))
					{
						// Patterns: "len: ..123" or "len: 1..123"
						return intval($matches['max']);
					}
					elseif (!empty($matches['min']) && empty($matches['dots']))
					{
						// Pattern: "len: 5" (exact length)
						return intval($matches['min']);
					}
				}
			}
		}

		return null;
	}

	/**
	 *	Extract enum values from Validates attribute enum: constraint
	 */
	private static function extractEnumValuesFromValidates(Validates $validates): ?array
	{
		$rules = array_filter([
			$validates->onCreate,
			$validates->onUpdate,
		]);

		foreach ($rules as $ruleString)
		{
			if ($ruleString === null)
			{
				continue;
			}

			// Parse comma-separated rules
			$parts = array_map('trim', explode(',', $ruleString));

			foreach ($parts as $rule)
			{
				// Match enum: pattern (e.g., "enum: foo, bar, baz")
				if (preg_match('/^enum:\s*(.+)$/', $rule, $matches))
				{
					// Extract and clean enum values
					$enumString = $matches[1];
					$values = array_map('trim', explode(',', $enumString));

					return array_filter($values);
				}
			}
		}

		return null;
	}

	private static function toSqlType(string $phpType, bool $isPrimaryKey, bool $isTimestamp, bool $timestampDefault, ?int $maxLength = null, ?array $enumValues = null): string
	{
		if ($isPrimaryKey && $phpType === 'int')
		{
			return 'INT UNSIGNED AUTO_INCREMENT';
		}

		if ($isTimestamp)
		{
			if ($timestampDefault)
			{
				return 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL';
			}

			return 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP';
		}

		// If enum values are provided, generate ENUM type
		if ($enumValues !== null && !empty($enumValues))
		{
			$quotedValues = array_map(fn($v) => "'{$v}'", $enumValues);

			return 'ENUM(' . implode(', ', $quotedValues) . ')';
		}

		return match ($phpType)
		{
			'int' => 'INT',
			'float' => 'FLOAT',
			'bool' => 'TINYINT(1)',
			'string' => $maxLength !== null ? "VARCHAR({$maxLength})" : 'VARCHAR(255)',
			'array' => 'JSON',
			'object' => 'JSON',
			default => 'TEXT',
		};
	}

}
