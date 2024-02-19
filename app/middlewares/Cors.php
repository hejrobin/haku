<?php
declare(strict_types=1);

namespace App\Middlewares;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Delegation\Middleware;

use Haku\Http\{
	Request,
	Headers,
	Message
};

class cors extends Middleware
{

	protected string $allowOrigin = '*';

	protected array $allowMethods = ['GET', 'POST'];

	protected array $allowHeaders = [
		'Accept',
		'Accept-Language',
		'Content-Language',
		'Content-Type',
	];

	protected bool $allowCredentials = false;

	protected int $accessControlMaxAge = 3600 * 24;

	public function getAllowOrigin(): string
	{
		return $this->allowOrigin ?? '*';
	}

	public function getAllowMethods(): string
	{
		return implode(', ', array_unique(array_merge(['OPTIONS'], $this->allowMethods)));
	}

	public function getAllowHeaders(): string
	{
		return implode(', ', $this->allowHeaders);
	}

	public function getAllowCredentials(): string
	{
		return $this->allowCredentials === true ? 'true' : 'false';
	}

	public function getAccessControlMaxAge(): string
	{
		return "{$this->accessControlMaxAge}";
	}

	public function invoke(Request $request, Message $response, Headers $headers): array
	{
		$headers->set('Access-Control-Max-Age', $this->getAccessControlMaxAge());
		$headers->set('Access-Control-Allow-Origin', $this->getAllowOrigin());
		$headers->set('Access-Control-Allow-Methods', $this->getAllowMethods());
		$headers->set('Access-Control-Allow-Headers', $this->getAllowHeaders());
		$headers->set('Access-Control-Allow-Credentials', $this->getAllowCredentials());

		return [$request, $response, $headers];
	}

}
