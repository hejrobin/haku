<?php
declare(strict_types=1);

namespace Haku\Http\Messages;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Http\{
	Message,
	Status
};

class Plain extends Message
{

	public static function from(
		mixed $data,
		Status $status,
		array $headers = [],
	): self
	{
		return new self([$data], $status, $headers);
	}

	protected function render(array $data): string
	{
		return implode($data);
	}

	public function valid(): bool
	{
		return is_string($this->asRendered());
	}

}
