<?php
declare(strict_types=1);

namespace Haku\Exceptions;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class VendorException extends \Exception {}
