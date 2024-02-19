<?php
declare(strict_types=1);

namespace Haku\Database\Mixins;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;


use \ReflectionClass;
use \ReflectionProperty;
use \ReflectionAttribute;

use Haku\Database\Exceptions\{
	DatabaseException,
	EntityException
};

use Haku\Database\Attributes\{
	PrimaryKey,
	Timestamp,
	Omitted,
	Included,
	Validates
};

use Haku\Schema\{
	Validator,
	ValidatorResultSet,
};

enum EntityValidationType: string {
	case Create = 'onCreate';
	case Update = 'onUpdate';
}

trait Entity
{

	protected readonly string $tableName;
	protected readonly string $primaryKeyName;

	protected readonly array $validationRules;
	protected readonly array $timestampFields;
	protected readonly array $omittedFields;
	protected readonly array $includedFields;

	protected bool $isValid = false;
	protected bool $hasChanges = false;

	/**
	 *	Fetches attribute data of current model.
	 *
	 *	@throws Haku\Database\Exceptions\EntityException
	 */
	protected function prepareEntity(): void
	{
		$ref = new ReflectionClass(static::class);

		$attributes = $ref->getAttributes();
		$hasAttributes = count($attributes) > 0;

		if (!$hasAttributes)
		{
			throw new EntityException('Model is missing a Entity attribute.');
		}

		if ($hasAttributes)
		{
			$firstAttribute = array_shift($attributes);

			$entity = $firstAttribute->newInstance();
			$this->tableName = $entity->tableName;
		}
	}

	/**
	 *	Prepares database Entity with attributes, also adds validation rules.
	 */
	protected function prepareEntityProperties(): void
	{
		$ref = new ReflectionClass(static::class);

		$validationRules = [];
		$omittedFields = [];
		$includedFields = [];
		$timestampFields = [];

		$properties = $ref->getProperties();

		$validAttributeNames = [
			PrimaryKey::class,
			Timestamp::class,
			Omitted::class,
			Included::class,
			Validates::class,
		];

		foreach ($properties as $property)
		{
			$attributes = $property->getAttributes();
			$propertySetter = $this->getSetterFromProperty($property->getName());

			$ignoredReadOnlyProperties = [
				'tableName',
				'primaryKeyName',
				'validationRules',
				'timestampFields',
				'omittedFields',
			];

			$propertyIsIgnored =
				in_array($property->getName(), $ignoredReadOnlyProperties) === true;

			// Warn when using read-only properties without a setter
			if (
				$property->isReadOnly() &&
				$propertyIsIgnored === false &&
				method_exists($this, $propertySetter) === false
			) {
				throw new EntityException(
					sprintf(
						"Read-only property '%s' requires a setter, implement '%s'.",
						$property->getName(),
						$propertySetter,
					),
				);
			}

			// Warn when using private properties without a setter
			if (
				$property->isPrivate() &&
				$propertyIsIgnored === false &&
				method_exists($this, $propertySetter) === false
			) {
				throw new EntityException(
					sprintf(
						"Private property '%s' requires a setter, implement '%s'.",
						$property->getName(),
						$propertySetter,
					),
				);
			}

			// Get all attributes
			// @link https://php.watch/versions/8.0/attributes
			$attributes = array_filter($attributes, function (
				ReflectionAttribute $attribute,
			) use ($validAttributeNames) {
				return in_array($attribute->getName(), $validAttributeNames);
			});

			$hasAttributes = count($attributes) > 0;

			// Parse all defined attributes
			if ($hasAttributes)
			{
				foreach ($attributes as $attribute)
				{
					$attr = $attribute->newInstance();

					switch ($attribute->getName())
					{
						case PrimaryKey::class:
							$this->primaryKeyName = $property->getName();

							if ($property->isReadOnly() === false) {
								throw new EntityException('Primary key must be readonly.');
							}
							break;
						case Timestamp::class:
							if ($property->isReadOnly() === false) {
								throw new EntityException(
									sprintf("Timestamp '%s' must be readonly.", $property->getName()),
								);
							}

							$timestampFields[] = $property->getName();
							break;
						case Validates::class:
							// Parse validation rules
							$onCreate = array_map('trim', explode('|', $attr->onCreate ?? ''));
							$onUpdate = array_map('trim', explode('|', $attr->onUpdate ?? $attr->onCreate));

							$validationRules[$property->getName()] = [
								EntityValidationType::Create->value => $onCreate,
								EntityValidationType::Update->value => $onUpdate,
							];
							break;
						case Omitted::class:
							$omittedFields[] = $property->getName();
							break;
						case Omitted::class:
							$includedFields[] = $property->getName();
							break;
					}
				}
			}
		}

		$this->validationRules = $validationRules;
		$this->timestampFields = $timestampFields;
		$this->omittedFields = $omittedFields;
		$this->includedFields = $includedFields;
	}

	/**
	 *	Returns setter name for a property, e.g. "setSomeProperty"
	 */
	protected function getSetterFromProperty(string $property): string
	{
		return 'set' . ucfirst($property);
	}

	/**
	 *	Assigns values to current entity.
	 */
	public function assign(array $record, bool $notifyChange = true)
	{
		$this->isValid = false;
		$this->hasChanges = $notifyChange;

		foreach ($record as $field => $value)
		{
			$setter = $this->getSetterFromProperty($field);

			// Use defined setters first, fallback to attempt to set.
			// @NOTE Read-only properties will trigger errors
			if (method_exists($this, $setter)) {
				$this->$setter($value);
			} else {
				$this->$field = $value;
			}
		}
	}

	/**
	 *	Validates whether or not model is persistent (if primary key is initialized and set).
	 */
	public function isPersistent(): bool
	{
		$ref = new ReflectionProperty(static::class, $this->primaryKeyName);
		$hasPrimaryKey = $ref->isInitialized($this) && $ref->getValue($this) > 0;

		return $hasPrimaryKey;
	}

	public function getPrimaryKey(): ?int
	{
		if ($this->isPersistent()) {
			$ref = new ReflectionProperty(static::class, $this->primaryKeyName);

			return $ref->getValue($this);
		}

		return null;
	}

	public function isSoftDeleteable(): bool
	{
		return method_exists($this, 'hasDeletedAt');
	}

	/**
	 *	Returns an array of validator resultsets.
	 *
	 *	@return ValidationResultSet[]
	 */
	protected function validateByType(EntityValidationType $validationType): array
	{
		$validator = new Validator();
		$record = $this->getRecord();

		$validationResults = [];
		$validations = [];

		// Assume validation will succeed, set accordingly in validation loop.
		$this->isValid = true;

		foreach ($this->validationRules as $field => $context)
		{
			$validations[$field] = $context[$validationType->value];
		}

		foreach ($validations as $field => $rules)
		{
			if (array_key_exists($field, $record))
			{
				/* Make sure requiredWith only triggers when it's compareField is set */
				$requiredWith = array_filter($rules, function (string $rule) {
					return str_starts_with($rule, 'requiredWith');
				});

				if (count($requiredWith) > 0)
				{
					$requiredWith = $requiredWith[0];
					$requiredWithField = array_pop(
						explode(':', str_replace(' ', '', $requiredWith)),
					);

					if (!array_key_exists($requiredWithField, $this->validationRules))
					{
						throw new ValidatorException(
							'Required with field not present in validations.',
						);
					}

					$requiredWithFieldRules =
						$this->validationRules[$requiredWithField][$validationType->value];

					// Required field is optional and not set, skip validation(s)
					if (
						in_array('optional', $requiredWithFieldRules) &&
						empty($record[$requiredWithField])
					) {
						continue;
					}
				}

				$result = $validator->validateAll($rules, $field, $record);

				if ($result->success === false)
				{
					$validationResults[$field] = $result->errors;
				}

				// Update failed validation
				if ($result->success === false)
				{
					$this->isValid = false;
				}
			}
		}

		return $validationResults;
	}

	public function validate(): array
	{
		return $this->validateByType(
			$this->isPersistent() ? EntityValidationType::Update : EntityValidationType::Create,
		);
	}

	/**
	 *	Returns array of exposeable property names.
	 */
	protected function getExposeablePropertyNames(): array
	{
		$exposeableFields = array_unique(
			array_keys($this->validationRules, $this->get_included_files)
		);

		return [
			$this->primaryKeyName,
			...$exposeableFields,
			...$this->timestampFields
		];
	}

	/**
	 *	Returns record data from current entity.
	 */
	protected function getRecord(
		bool $filterPrivate = false,
		bool $filterOmitted = true,
		bool $filterTimestamps = false,
	): array
	{
		$ref = new ReflectionClass(static::class);

		$record = [];

		$properties = $ref->getProperties();
		$validProperties = $this->getExposeablePropertyNames();

		foreach ($properties as $property)
		{
			if (in_array($property->getName(), $validProperties))
			{
				$propertyValue = null;

				if ($property->isInitialized($this))
				{
					$propertyValue = $property->getValue($this);
				}

				if ($filterPrivate && $property->isPrivate())
				{
					continue;
				}

				if (
					$filterOmitted === true &&
					in_array($property->getName(), $this->omittedFields)
				) {
					continue;
				}

				if (
					$filterTimestamps === true &&
					in_array($property->getName(), $this->timestampFields)
				) {
					continue;
				}

				$record[$property->getName()] = $propertyValue;
			}
		}

		return $record;
	}

	protected function getRecordFields(
		bool $filterPrivate = false,
		bool $filterOmitted = true,
		bool $filterTimestamps = false,
	): array
	{
		return array_keys(
			$this->getRecord($filterPrivate, $filterOmitted, $filterTimestamps),
		);
	}

}
