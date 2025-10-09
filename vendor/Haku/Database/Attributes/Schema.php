<?php
declare(strict_types=1);

namespace Haku\Database\Attributes;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use \Attribute;

/**
 *	Schema attribute for overriding property SQL definition in migrations
 *	Example: #[Schema('TEXT NOT NULL')]
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Schema
{

	public function __construct(
		public string $definition
	) {}

}
