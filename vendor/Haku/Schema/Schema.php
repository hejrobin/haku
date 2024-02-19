<?php
declare(strict_types=1);

namespace Haku\Schema;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Schema
{

	protected Validator $validator;

	public function __construct(protected array $schema) {
		$this->validator = new Validator();
	}

	public function validate(array $record): array {
		$result = [];

		foreach($this->schema as $field => $rules)
		{
			$result[$field] = $this->validator->validateAll($rules, $field, $record);
		}

		return $result;
	}

	public function validates(array $record): bool {
		$willValidate = true;

		foreach($this->schema as $field => $rules)
		{
			$result = $this->validator->validateAll($rules, $field, $record);

			if ($result->success === false) {
				$willValidate = false;
				break;
			}
		}

		return $willValidate;
	}

}
