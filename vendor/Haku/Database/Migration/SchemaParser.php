<?php
declare(strict_types=1);

namespace Haku\Database\Migration;

use Haku\Database\Attributes\IncludeType;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use ReflectionClass;
use ReflectionProperty;

use Haku\Exceptions\FrameworkException;
use Haku\Database\Attributes\{
	Entity,
	Schema,
	PrimaryKey,
	Validates,
	Timestamp,
	Relation
};
use Haku\Database\RelationType;

use function Haku\resolvePath;
use function Haku\Generic\Strings\snakeCaseFromCamelCase;

class SchemaParser
{

	private string $tableName;

	private array $properties = [];

	private array $primaryIndexes = [];

	private array $uniqueIndexes = [];

	private array $foreignKeys = [];

	public function parse(string $modelName)
	{
		$paths = [
			[
				'path' => resolvePath('app', 'models', "{$modelName}.php"),
				'namespace' => "App\\Models\\{$modelName}"
			],
			[
				'path' => resolvePath('testing', 'models', "{$modelName}.php"),
				'namespace' => "Testing\\Models\\{$modelName}"
			],
		];

		$path = null;
		$name = null;

		foreach ($paths as $candidate)
		{
			if (file_exists($candidate['path']))
			{
				$path = $candidate['path'];
				$name = $candidate['namespace'];
				break;
			}
		}

		if ($path === null)
		{
			throw new FrameworkException(sprintf('Model "%s" not found in app/models or testing/models', $modelName));
		}

		require_once $path;

		if (!class_exists($name))
		{
			throw new FrameworkException(sprintf('Model class "%s" not found', $name));
		}

		$reflection = new ReflectionClass($name);
		$attributes = $reflection->getAttributes(Entity::class);

		if (count($attributes) === 0)
		{
			throw new FrameworkException(sprintf('Model "%s" is missing #[Entity] attribute', $name));
		}

		$entity = $attributes[0]->newInstance();

		$tableName = $entity->tableName;
		$this->tableName = $tableName;

		$properties = $reflection->getProperties(
			ReflectionProperty::IS_PUBLIC |
			ReflectionProperty::IS_PROTECTED |
			ReflectionProperty::IS_PRIVATE
		);

		foreach ($properties as $property)
		{
			if ($property->getDeclaringClass()->getName() !== $name)
			{
				continue;
			}

			$columnName = snakeCaseFromCamelCase($property->getName());

			$this->parsePrimaryKey($columnName, $property);
			$this->parseSchema($columnName, $property);
			$this->parseTimestamp($columnName, $property);
			$this->parseValidates($columnName, $property);
			$this->parseRelation($columnName, $property);
		}
	}

	private function allowsNull(ReflectionProperty $property): bool
	{
		$type = $property->getType();

		if (!$type)
		{
			return false;
		}

		return $type->allowsNull();
	}

	private function parsePrimaryKey(string $columnName, ReflectionProperty $property): void
	{
		$attrs = $property->getAttributes(PrimaryKey::class);

		if (!empty($attrs))
		{
			$this->primaryIndexes[] = $columnName;

			$this->properties[$columnName] = 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
		}
	}

	private function parseSchema(string $columnName, ReflectionProperty $property): void
	{
		$attrs = $property->getAttributes(Schema::class);

		if (!empty($attrs))
		{
			$schema = $attrs[0]->newInstance();

			$definition = $schema->toSQL();
			$definition .= $this->allowsNull($property) ? ' NULL' : ' NOT NULL';

			$this->properties[$columnName] = $definition;
		}
	}

	private function parseTimestamp(string $columnName, ReflectionProperty $property): void
	{
		$attrs = $property->getAttributes(Timestamp::class);

		if (!empty($attrs))
		{
			$timestamp = $attrs[0]->newInstance();
			$definition = 'TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP';

			if ($timestamp->default)
			{
				$definition = 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL';
			}

			$this->properties[$columnName] = $definition;
		}
	}

	private function parseValidates(string $columnName, ReflectionProperty $property): void
	{
		$attrs = $property->getAttributes(Validates::class);

		if (!empty($attrs))
		{
			foreach ($attrs as $attr)
			{
				$validates = $attr->newInstance();
				$ruleString = "{$validates->onCreate} | {$validates->onUpdate}";
				$rules = preg_split('/\s*[|]\s*/', $ruleString);

				foreach ($rules as $rule)
				{
					$this->parseValidatesLength(
						$rule,
						$columnName,
						$property,
						$rules
					);
				}
			}
		}
	}

	private function parseValidatesLength(
		string $rule,
		string $columnName,
		ReflectionProperty $property,
		array $rules,
	): void
	{
		$length = 0;

		if (preg_match('/^len:\s*(?P<min>\d+)?(?P<dots>\.\.)?(?P<max>\d+)?$/', $rule, $matches))
		{
			if (!empty($matches['max']))
			{
				// Patterns: "len: ..123" or "len: 1..123" or "len:3..64"
				$length = intval($matches['max']);
			}
			elseif (!empty($matches['min']) && empty($matches['dots']))
			{
				// Pattern: "len: 5" or "len:5" (exact length)
				$length = intval($matches['min']);
			}
		}

		switch ($rule)
		{
			case 'emailAddress':
			case 'strongPassword':
				$length = 255;
				break;
		}

		if ($length > 0)
		{
			$definition = sprintf('VARCHAR(%d)', $length);


			if (in_array('unique', $rules))
			{
				$definition .= ' UNIQUE';
			}

			$definition .= $this->allowsNull($property) ? ' NULL' : ' NOT NULL';

			$this->properties[$columnName] = $definition;
		}
	}

	private function parseRelation(string $columnName, ReflectionProperty $property): void
	{
		$attrs = $property->getAttributes(Relation::class);

		if (!empty($attrs))
		{
			$relation = $attrs[0]->newInstance();

			if ($relation->type === RelationType::BelongsTo)
			{
				$foreignKeyColumn = $relation->foreignKey ?? $columnName;

				$referencedTable = $this->getTableNameFromModel($relation->model);

				$referencedColumn = 'id';

				$this->foreignKeys[] = [
					'column' => $foreignKeyColumn,
					'referencedTable' => $referencedTable,
					'referencedColumn' => $referencedColumn,
				];

				if (!isset($this->properties[$foreignKeyColumn]))
				{
					$definition = 'BIGINT UNSIGNED';
					$definition .= $this->allowsNull($property) ? ' NULL' : ' NOT NULL';

					$this->properties[$foreignKeyColumn] = $definition;
				}
			}
		}
	}

	private function getTableNameFromModel(string $modelName): string
	{
		$paths = [
			[
				'path' => resolvePath('app', 'models', "{$modelName}.php"),
				'namespace' => "App\\Models\\{$modelName}"
			],
			[
				'path' => resolvePath('testing', 'models', "{$modelName}.php"),
				'namespace' => "Testing\\Models\\{$modelName}"
			],
		];

		$path = null;
		$className = null;

		foreach ($paths as $candidate)
		{
			if (file_exists($candidate['path']))
			{
				$path = $candidate['path'];
				$className = $candidate['namespace'];
				break;
			}
		}

		if ($path === null)
		{
			throw new FrameworkException(sprintf('Related model "%s" not found in app/models or testing/models', $modelName));
		}

		require_once $path;

		if (!class_exists($className))
		{
			throw new FrameworkException(sprintf('Related model class "%s" not found', $className));
		}

		$reflection = new ReflectionClass($className);
		$attributes = $reflection->getAttributes(Entity::class);

		if (count($attributes) === 0)
		{
			throw new FrameworkException(sprintf('Related model "%s" is missing #[Entity] attribute', $className));
		}

		$entity = $attributes[0]->newInstance();

		return $entity->tableName;
	}

	public function toCreateSQL(): string
	{
		$sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (\n";

		$columns = [];

		foreach ($this->properties as $column => $definition)
		{
			$columns[] = "\t`{$column}` {$definition}";
		}

		$sql .= implode(",\n", $columns);

		// Add foreign key constraints
		if (!empty($this->foreignKeys))
		{
			foreach ($this->foreignKeys as $fk)
			{
				$constraintName = "fk_{$this->tableName}_{$fk['column']}";

				$sql .= ",\n\tCONSTRAINT `{$constraintName}` FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$fk['referencedTable']}`(`{$fk['referencedColumn']}`) ON DELETE CASCADE ON UPDATE CASCADE";
			}
		}

		$sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

		return $sql;
	}

	public function toDropSQL(): string
	{
		return "DROP TABLE IF EXISTS `{$this->tableName}`;";
	}

}


