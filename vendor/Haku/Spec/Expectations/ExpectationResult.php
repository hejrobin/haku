<?php
declare(strict_types=1);

namespace Haku\Spec\Expectations;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

final class ExpectationResult
{
	public function __construct(
		public readonly bool $success,
		public readonly ?string $message
	) {}

	public function toArray(): array
	{
		return [
			$this->success,
			$this->message
		];
	}
}
