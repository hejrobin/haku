<?php
declare(strict_types=1);

namespace Haku\Generic\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Generic\Query\{
	FilterOperator,
	FilterProperty,
};

use function Haku\Generic\Arrays\find;

class Filter
{

	protected array $filters = [];

	/**
	 *	Parses a JSON string of objects.
	 *
	 *	@param array $unresolved
	 */
	public static function from(array $unresolved): self
	{
		$self = new self();

		foreach ($unresolved as $property)
		{
			$operator = $property['operator'];

			if (
				is_array($operator) &&
				array_key_exists('value', $operator)
			) {
				$operator = $operator['value'];
			}

			$self->add(
				$property['name'],
				FilterOperator::from($operator),
				$property['values'],
			);
		}

		return $self;
	}

	public function has(
		string $name,
		FilterOperator $operator,
	): bool
	{
		return $this->get($name, $operator) !== null;
	}

	public function get(
		string $name,
		FilterOperator $operator,
	): ?FilterProperty {
		return find($this->filters, function(FilterProperty $property) use ($name, $operator) {
			return $property->name === $name && $property->operator === $operator;
		});
	}

	public function add(
		string $name,
		FilterOperator $operator,
		array $values,
	) {
		if (!$this->has($name, $operator))
		{
			$this->filters[] = new FilterProperty($name, $operator, $values);
		}
	}

	public function remove(
		string $name,
		FilterOperator $operator,
	): void
	{
		$this->filters = array_filter($this->filters, function(FilterProperty $property) use ($name, $operator) {
			return $property->name !== $name && $property->operator !== $operator;
		});
	}

	public function getFilters(): array
	{
		return $this->filters;
	}

	public function toString(): string
	{
		return json_encode($this->filters);
	}

	public function __toString(): string
	{
		return $this->toString();
	}

}
