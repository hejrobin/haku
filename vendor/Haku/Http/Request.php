<?php
declare(strict_types=1);

namespace Haku\Http;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Request
{

	public function __construct(
		private string $name,
		private string $path,
		private string $pattern,
		private Method $method,
		private array $callback,
		private array $middlewares,
		private ?Headers $headers,
		private array $parameters,
	) {
		if (!$headers)
		{
			$this->headers = new Headers();
		}
	}

	public static function from(
		array $route,
		Headers $headers = null,
	): self
	{
		return new self(
			name: $route['name'],
			path: $route['path'],
			pattern: $route['pattern'],
			method: $route['method'],
			callback: $route['callback'],
			middlewares: $route['middlewares'] ?? [],
			headers: $headers,
			parameters: $route['parameters'] ?? [],
		);
	}

	private function prepareResponse(): Message
	{
		[$class, $method] = $this->callback;

		return call_user_func(
			[new $class, $method],
			...$this->parameters
		);
	}

	public function process(): array
	{
		$response = $this->prepareResponse();
		$request = $this;

		if (count($this->middlewares) > 0)
		{
			foreach($this->middlewares as $middleware)
			{
				[$req, $res, $head] = call_user_func_array(
					[new $middleware, 'invoke'],
					[$request, $response, $this->headers]
				);

				$request = $req;
				$response = $res;
			}
		}

		$this->headers->status($response->status);
		$this->headers->append($response->headers);

		return [$request, $response, $this->headers];
	}

}
