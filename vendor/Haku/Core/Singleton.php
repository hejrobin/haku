<?php
declare(strict_types=1);

namespace Haku\Core;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

trait Singleton
{

	protected static $__instance;

	/**
	 *	Returns self initialized instance.
	 */
	public static function getInstance(): self
	{
		if (is_object(self::$__instance) === false)
		{
			self::$__instance = new self();
		}

		return self::$__instance;
	}

	private function __construct() {}

	final public function __wakeup() {}

	final public function __clone() {}

}
