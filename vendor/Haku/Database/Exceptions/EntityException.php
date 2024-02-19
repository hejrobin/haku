<?php
declare(strict_types=1);

namespace Haku\Database\Exceptions;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Exceptions\FrameworkException;

class EntityException extends FrameworkException {}
