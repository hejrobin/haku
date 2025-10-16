<?php
declare(strict_types=1);

namespace Haku\Http;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

enum Method
{

	case Head;
	case Options;
	case Get;
	case Post;
	case Put;
	case Patch;
	case Delete;
	case Trace;
	case Connect;

	public static function resolve(): self
	{
		$method = strtolower($_SERVER['REQUEST_METHOD']) ?? 'get';

		if (
			empty($_POST) === false &&
			array_key_exists('_METHOD', $_POST) === true
		) {
			$method = strtolower($_POST['_METHOD']);
		}

		$method = mb_ucfirst($method);

		return match($method)
		{
			'Head' => Method::Head,
			'Options' => Method::Options,
			'Get' => Method::Get,
			'Post' => Method::Post,
			'Put' => Method::Put,
			'Patch' => Method::Patch,
			'Delete' => Method::Delete,
			'Trace' => Method::Trace,
			'Connect' => Method::Connect,
		};
	}

	public function asString(): string
	{
		return strtoupper($this->name);
	}

	/**
	 *	@NOTE Even though every request method except TRACE can theoretically use a payload,
	 *	for simplicity and common usage, only allow it for PUT, POST and PATCH.
	 */
	public function allowsPayload(): bool
	{
		return match ($this)
		{
			static::Put => true,
			static::Post => true,
			static::Patch => true,
			default => false
		};
	}
}
