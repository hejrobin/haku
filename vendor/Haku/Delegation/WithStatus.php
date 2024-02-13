<?php
declare(strict_types=1);

namespace Haku\Delegation;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Attribute;

#[Attribute(
	Attribute::TARGET_METHOD |
	Attribute::TARGET_CLASS
)]
class WithStatus
{

	public function __construct(
		private int $statusCode
	) {}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

}
