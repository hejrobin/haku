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
};

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
				$primaryKeys[] = $property->getName();
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
		$columnName = $property->getName();

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


		$sqlType = self::toSqlType($typeName, $isPrimaryKey, $isTimestamp, $timestampDefault);

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

	private static function toSqlType(string $phpType, bool $isPrimaryKey, bool $isTimestamp, bool $timestampDefault): string
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

		return match ($phpType)
		{
			'int' => 'INT',
			'float' => 'FLOAT',
			'bool' => 'TINYINT(1)',
			'string' => 'VARCHAR(255)',
			'array' => 'JSON',
			'object' => 'JSON',
			default => 'TEXT',
		};
	}

}
