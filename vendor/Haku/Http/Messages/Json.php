<?php
declare(strict_types=1);

namespace Haku\Http\Messages;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Http\{
	Message,
	Status
};

define('DEFAULT_PRETTY_PRINT', HAKU_ENVIRONMENT === 'dev');

class Json extends Message
{

	protected bool $formatNumbers;
	protected bool $prettyPrint;

	public static function from(
		mixed $data,
		Status $status = Status::OK,
		array $headers = [],
		bool $formatNumbers = true,
		bool $prettyPrint = DEFAULT_PRETTY_PRINT,
	): self
	{
		$self = new self((array) $data, $status, $headers);

		$self->formatNumbers = $formatNumbers;
		$self->prettyPrint = $prettyPrint;

		return $self;
	}

	public static function error(
		string $message,
		Status $status = Status::NotFound
	): self
	{
		return new self([ 'error' => $message ], $status, []);
	}

	private function resolveEncodeOptions(): int
	{
		$prettyPrint = $this->prettyPrint ? \JSON_PRETTY_PRINT : 0;
		$formatNumbers = $this->formatNumbers ? \JSON_NUMERIC_CHECK : 0;

		return $prettyPrint | $formatNumbers;
	}

	protected function render(array $data): string
	{
		return json_encode($data, $this->resolveEncodeOptions());
	}

	public function valid(): bool
	{
		return json_validate($this->asRendered());
	}

}
