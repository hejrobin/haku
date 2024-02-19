<?php
declare(strict_types=1);

namespace Haku\Database\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class OrWhere extends Where
{

	protected static function getGlue(): string
	{
		return 'OR';
	}

}
