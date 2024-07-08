<?php
declare(strict_types=1);

namespace Haku\Delegation\Middlewares;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Delegation\Middleware;

use Haku\Http\{
	Request,
	Headers,
	Message,
	Status
};

use Haku\Http\Messages\{
	Json,
	Plain
};

use Haku\Jwt\Token;

use function Haku\Jwt\currentToken;

class Jwt extends Middleware
{

	public array $requestHeaders = [];

	public function __construct()
	{
		$this->requestHeaders = \getallheaders();
	}

	private function transformResponseMessage(
		Headers $headers,
		array $message
	): array
	{
		$contentType = $headers->get('Content-Type');

		$response = match ($contentType)
		{
			'application/json' => Json::from($message),
			default => Plain::from($message),
		};

		return [$response, $headers];
	}

	private function transformToErrorResponse(
		Headers $headers,
		Status $status,
	): array
	{
		$headers->status($status);

		$contentType = $headers->get('Content-Type');
		$message = [ 'error' => $headers->getStatus()->getName() ];

		return $this->transformResponseMessage($headers, $message);
	}

	private function validAuthorizationHeader(): bool
	{
		if (array_key_exists('Authorization', $this->requestHeaders))
		{
			return true;
		}

		return false;
	}

	private function validAuthorizationToken(): bool
	{
		return currentToken() !== null;
	}

	public function invoke(
		Request $request,
		Message $response,
		Headers $headers
	): array
	{
		if (!$this->validAuthorizationHeader())
		{
			[$response, $headers] = $this->transformToErrorResponse(
				$headers,
				Status::Unauthorized
			);
		}

		if ($this->validAuthorizationToken())
		{
			$token = currentToken();

			if ($token === null)
			{
				[$response, $headers] = $this->transformToErrorResponse(
					$headers,
					Status::Unauthorized
				);
			}
			else
			{
				if ($token->hasExpired())
				{
					[$response, $headers] = $this->transformResponseMessage(
						$headers,
						[
							'error' => 'Authorization token has expired'
						]
					);

					$headers->status(Status::Unauthorized);
				}
			}

		}

		return [$request, $response, $headers];
	}

}
