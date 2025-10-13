<?php
declare(strict_types=1);

namespace Haku\Core;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

final class Kernel
{

	use Singleton;
	use Factory;

}
