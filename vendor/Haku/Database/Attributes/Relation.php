<?php
declare(strict_types=1);

namespace Haku\Database\Attributes;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use \Attribute;
use Haku\Database\RelationType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Relation
{

	public function __construct(
		public string $model,
		public RelationType $type,
		public ?string $foreignKey = null,
	) {}

}
