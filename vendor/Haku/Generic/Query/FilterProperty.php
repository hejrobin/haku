<?php
declare(strict_types=1);

namespace Haku\Generic\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Generic\Query\FilterOperator;

class FilterProperty
{

	public function __construct(
		public readonly string $name,
		public readonly FilterOperator $operator,
		public readonly array $values
	) {}

}
