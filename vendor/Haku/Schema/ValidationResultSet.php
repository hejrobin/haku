<?php
declare(strict_types=1);

namespace Haku\Schema;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class ValidationResultSet
{

	public function __construct(
		public bool $success,
		public array $errors = []
	) {}

}
