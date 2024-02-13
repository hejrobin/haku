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
class Uses
{

	private array $middlewares;

	public function __construct(
		string ...$middlewares
	)
	{
		$this->middlewares = $middlewares;
	}

	public function getMiddlewares(): array
	{
		return $this->middlewares;
	}

}
