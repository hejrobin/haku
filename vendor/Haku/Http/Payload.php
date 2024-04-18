<?php
declare(strict_types=1);

namespace Haku\Http;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Payload
{
	protected string $raw;

	public function __construct()
	{
		$this->raw = file_get_contents('php://input');
	}

	public function raw(): string
	{
		return $this->raw;
	}

	public function json(): ?object
	{
		if (json_validate($this->raw))
		{
			return json_decode($this->raw);
		}

		return (object) [];
	}

}
