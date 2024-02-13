<?php
declare(strict_types=1);

namespace Haku\Http\Exceptions;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Exceptions\FrameworkException;

use Haku\Http\Status;

class StatusException extends FrameworkException
{

	public function __construct(
		int $statusCode
	) {
		$status = Status::from($statusCode);

		parent::__construct(
			$status->getName(),
			$status->getCode()
		);
	}

}
