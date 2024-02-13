<?php
declare(strict_types=1);

namespace Haku\Delegation;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Attribute;

#[Attribute(
	Attribute::TARGET_METHOD |
	Attribute::TARGET_CLASS |
	Attribute::IS_REPEATABLE
)]
class WithHeaders
{

	public function __construct(
		private array $headers
	) {}

	public function getHeaders(): array
	{
		return $this->headers;
	}

}
