<?php
declare(strict_types=1);

namespace Haku\Database\Attributes;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use \Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Entity
{

	public function __construct(
		public string $tableName,
	) {}

}
