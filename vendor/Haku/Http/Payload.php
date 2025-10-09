<?php
declare(strict_types=1);

namespace Haku\Http;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Schema\Schema;

class Payload
{
	protected string $raw;

	protected object $json;

	protected Schema $schema;

	public function __construct(array $schema)
	{
		$this->schema = new Schema($schema);
		$this->raw = getRawRequestPayload();

		if (json_validate($this->raw))
		{
			$this->json = json_decode($this->raw);
		}
		else
		{
			$this->json = (object) [];
		}
	}

	public function errors(): array
	{
		$errors = [];

		$results = $this->schema->validate((array) $this->json);

		foreach ($results as $field => $result)
		{
			if ($result->success === false)
			{
				$errors[$field] = $result->errors;
			}
		}

		return $errors;
	}

	public function raw(): string
	{
		return $this->raw;
	}

	public function json(): object
	{
		return $this->json;
	}

}
