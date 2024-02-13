<?php
declare(strict_types=1);

namespace Haku\Http;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

/**
 *	Container for anything that is presented to the client.
 */
abstract class Message
{

	public function __construct(
		protected array $data = []
	) {}

	protected function sanitize(mixed $value, string $type = 'string'): string
	{
		$type = strtolower($type);

		$filterType = FILTER_SANITIZE_FULL_SPECIAL_CHARS;
		$filterFlags = 0;

		switch ($type)
		{
			case 'url':
				$filterType = FILTER_SANITIZE_URL;
				break;

			case 'int':
				$filterType = FILTER_SANITIZE_NUMBER_INT;
				break;

			case 'float':
				$filterType = FILTER_SANITIZE_NUMBER_FLOAT;
				$filterFlags = FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND;
				break;

			case 'email':
				$filterType = FILTER_SANITIZE_EMAIL;
				break;

			case 'string':
			default:
				$filterType = FILTER_SANITIZE_FULL_SPECIAL_CHARS;
				$filterFlags = FILTER_FLAG_NO_ENCODE_QUOTES;
				break;
		}

		return filter_var($value, $filterType, $filterFlags);
	}

	public function set(
		string $name,
		mixed $value,
		string $type = 'string'
	): void
	{
		$this->data[$name] = $this->sanitize($value, $type);
	}

	public function get(
		string $name
	): mixed
	{
		if (array_key_exists($name, $this->data))
		{
			return $this->data[$name];
		}

		return null;
	}

	public function remove(
		string $name
	): void
	{
		if (array_key_exists($name, $this->data))
		{
			unset($this->data[$name]);
		}
	}

	abstract protected function render(array $data): string;

	abstract public static function from(mixed $data): self;

	abstract public function valid(): bool;

	public function size(): int
	{
		return strlen($this->asRendered());
	}

	public function asRendered(): string
	{
		return $this->render($this->data);
	}

	public function __toString(): string
	{
		return $this->asRendered();
	}

}
