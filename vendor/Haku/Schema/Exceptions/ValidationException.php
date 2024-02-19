<?php
declare(strict_types=1);

namespace Haku\Schema\Exceptions;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Exceptions\FrameworkException;

class ValidationException extends FrameworkException {}
