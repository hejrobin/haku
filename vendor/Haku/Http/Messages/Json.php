<?php
declare(strict_types=1);

namespace Haku\Http\Messages;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Http\{
	Message,
	Status
};

class Json extends Message
{

	public static function from(
		mixed $data,
		Status $status = Status::OK,
		array $headers = [],
	): self
	{
		return new self((array) $data, $status, $headers);
	}

	public static function error(
		string $message,
		Status $status = Status::NotFound
	): self
	{
		return new self([ 'error' => $message ], $status, []);
	}

	protected function render(array $data): string
	{
		return json_encode($data, \JSON_PRETTY_PRINT | \JSON_NUMERIC_CHECK);
	}

	public function valid(): bool
	{
		return json_validate($this->asRendered());
	}

}
