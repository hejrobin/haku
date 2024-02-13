<?php
declare(strict_types=1);

namespace Haku\Delegation;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Attribute;

use Haku\Http\Method;

use function Haku\cleanPath;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Route
{

	// Matches {foo}, {foo:number} and {foo:number}?
	const REGEX_ROUTE_PARAMETER = '~{(?<parameter>([\w\-_%]+))(?:\:?(?<type>(\w+)))?}(?:(?<optional>\?))?~ix';

	private string $pattern;

	public function __construct(
		private string $path,
		private Method $method = Method::Get,
		private string $name = '',
	)
	{
		$this->path = cleanPath($path);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getMethod(): Method
	{
		return $this->method;
	}

}
