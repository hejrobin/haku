<?php
declare(strict_types=1);

namespace Haku\Generic\Query;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Params
{

	protected array $data = [];

	public function __construct(
		string $unresolved = ''
	) {
		if (
			strlen($unresolved) === 0 &&
			array_key_exists('QUERY_STRING', $_SERVER)
		) {
			$unresolved = $_SERVER['QUERY_STRING'];
		}

		parse_str($unresolved, $this->data);
	}

	public function has(string $key): bool
	{
		return array_key_exists($key, $this->data);
	}

	public function set(string $key, mixed $value)
	{
		if (is_null($value))
		{
			unset($this->data[$key]);
		}
		else
		{
			$this->data[$key] = $value;
		}
	}

	public function get(string $key): mixed
	{
		if ($this->has($key) === false)
		{
			return null;
		}

		return $this->data[$key];
	}

	public function toString(): string
	{
		return http_build_query($this->data, 'unnamed_');
	}

	public function __toString(): string
	{
		return $this->toString();
	}

}
