<?php
declare(strict_types=1);

namespace Haku\Schema;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class ValidationResult
{

	public function __construct(
		public bool $success,
		public string $error
	) {}

}
