<?php
declare(strict_types=1);

namespace Haku\Database\Attributes;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use \Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Validates
{

	public function __construct(
		public ?string $onCreate = null,
		public ?string $onUpdate = null,
	) {}

}
