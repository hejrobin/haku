<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	This is a very rudimentary (select) query builder class, in most cases {{@see Find}} are better,
 *	but this is used to write simple custom select queries.
 */
class Builder
{
	protected ?string $table = null;

	protected array $fields = [];
	protected array $joins = [];
	protected array $where = [];
	protected array $groupBy = [];
	protected array $having = [];
	protected array $orderBy = [];

	protected ?int $limit = null;
	protected ?int $offset = null;

	protected int $numParameters = 0;
	protected array $bindings = [];

	private function normalizeParameter(string $raw): string
	{
		return ':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $raw) . '_' . (++$this->numParameters);
	}

	public function select(string ...$fields): self
	{
		$this->fields = array_merge($this->fields, $fields);

		return $this;
	}

	public function from(string $table): self
	{
		$this->table = $table;

		return $this;
	}

	public function join(array $joins): self
	{
		foreach ($joins as $alias => $on)
		{
			$onClause = is_array($on) ? "{$on[0]} = {$on[1]}" : $on;
			$this->joins[] = "JOIN $alias ON $onClause";
		}

		return $this;
	}

	protected function addFieldCondition(
		string $type,
		string $field,
		mixed $value,
		string $glue
	): self
	{
		$param = $this->normalizeParameter($field);

		$this->bindings[$param] = $value;
		$condition = "$field $glue $param";

		$this->where[] = [$type, $condition];

		return $this;
	}

	protected function addRawCondition(
		string $type,
		string $condition,
		array $params
	): self
	{
		$replaced = $condition;

		foreach ($params as $param) {
			$placeholder = $this->normalizeParameter('param');
			$replaced = preg_replace('/\?/', $placeholder, $replaced, 1);

			$this->bindings[$placeholder] = $param;
		}

		$this->where[] = [$type, $replaced];

		return $this;
	}

	public function where(string $field, mixed $value, string $glue = '='): self
	{
		return $this->addFieldCondition('AND', $field, $value, $glue);
	}

	public function orWhere(string $field, mixed $value, string $glue = '='): self
	{
		return $this->addFieldCondition('OR', $field, $value, $glue);
	}

	public function whereRaw(string $condition, mixed ...$params): self
	{
		return $this->addRawCondition('AND', $condition, $params);
	}

	public function orWhereRaw(string $condition, mixed ...$params): self
	{
			return $this->addRawCondition('OR', $condition, $params);
	}

	public function groupBy(string ...$fields): self
	{
		$this->groupBy = array_merge($this->groupBy, $fields);

		return $this;
	}

	public function having(string $field, mixed $value, string $glue = '='): self {
    $param = $this->normalizeParameter($field);

		$this->bindings[$param] = $value;
		$this->having[] = "$field $glue $param";

		return $this;
	}

	public function havingRaw(string $condition, mixed ...$params): self
	{
		$replaced = $condition;

		foreach ($params as $param)
		{
			$placeholder = $this->normalizeParameter('having');
			$replaced = preg_replace('/\?/', $placeholder, $replaced, 1);
			$this->bindings[$placeholder] = $param;
		}

		$this->having[] = $replaced;

		return $this;
	}

	public function orderBy(string $field, string $direction = 'ASC'): self
	{
		$this->orderBy[] = "$field " . strtoupper($direction);

		return $this;
	}

	public function limit(int $limit): self
	{
		$this->limit = $limit;

		return $this;
	}

	public function offset(int $offset): self
	{
		$this->offset = $offset;

		return $this;
	}

	public function toSql(): string
	{
		if (!$this->table)
		{
			throw new \LogicException("No FROM clause specified.");
		}

		$sql = [];
		$sql[] = "SELECT " . ($this->fields ? implode(', ', $this->fields) : '*');
		$sql[] = "FROM {$this->table}";

		if ($this->joins)
		{
			$sql = array_merge($sql, $this->joins);
		}

		if ($this->where)
		{
			$clauses = [];

			foreach ($this->where as $i => [$type, $cond])
			{
				$clauses[] = ($i === 0 ? '' : " $type ") . $cond;
			}

			$sql[] = "WHERE " . implode('', $clauses);
		}

		if ($this->groupBy)
		{
			$sql[] = "GROUP BY " . implode(', ', $this->groupBy);
		}

		if ($this->having)
		{
			$sql[] = "HAVING " . implode(' AND ', $this->having);
		}

		if ($this->orderBy)
		{
			$sql[] = "ORDER BY " . implode(', ', $this->orderBy);
		}

		if ($this->limit !== null)
		{
			$limit = "LIMIT {$this->limit}";

			if ($this->offset !== null)
			{
				$limit .= ", {$this->offset}";
			}

			$sql[] = $limit;
		}

		return implode(' ', $sql);
	}

	public function getBindings(): array
	{
		return $this->bindings;
	}

}
