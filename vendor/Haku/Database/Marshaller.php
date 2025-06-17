<?php
declare(strict_types=1);

namespace Haku\Database;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Generic\Strings\{
	camelCaseFromSnakeCase,
	snakeCaseFromCamelCase,
};

/**
 *	 Converts record keys from snake_case (in database) to camel-case (for JSON).
 */
trait Marshaller
{

	/**
	 *	Changes the case of keys in a named object.
	 */
	protected function marshal(?array $record): array | null
	{
		if (!$record)
		{
			return null;
		}

		$marshalled = [];

		foreach ($record as $field => $value)
		{
			$marshalled[camelCaseFromSnakeCase($field)] = $value;
		}

		return $marshalled;
	}

	/**
	 *	Changes the case of keys in a named object.
	 */
	protected function unmarshal(?array $record): array | null
	{
		if (!$record)
		{
			return null;
		}

		$unmarshalled = [];

		foreach ($record as $field => $value)
		{
			$unmarshalled[snakeCaseFromCamelCase($field)] = $value;
		}

		return $unmarshalled;
	}

}
