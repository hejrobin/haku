<?php
declare(strict_types=1);

namespace Haku\Http;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Fetch
{
	private $curl;

	protected mixed $info;

	protected Headers $headers;

	/**
	 *	Prepares cURL
	 */
	public function __construct(
		protected string $uri,
		protected Method $method = Method::Get,
		array $headers = []
	) {
		$this->headers = new Headers($headers);

		$this->curl = curl_init($uri);
	}

	/**
	 *	Sets a curl option.
	 *
	 *	@see https://www.php.net/manual/en/function.curl-setopt.php
	 */
	public function setOption(int $option, mixed $value): bool
	{
		return curl_setopt($this->curl, $option, $value);
	}

	/**
	 *	Sets a curl options from array.
	 *
	 *	@see https://www.php.net/manual/en/function.curl-setopt-array.php
	 */
	public function setOptions(array $options): bool
	{
		return curl_setopt_array($this->curl, $options);
	}

	/**
	 *	Makes a JSON request
	 */
	public function json(array $payload = null): object
	{
		if (!$this->headers->has('Accept'))
		{
			$this->headers->set('Accept', 'application/json');
		}

		$this->headers->set('Content-Type', 'application/json');

		if ($this->method === Method::Post && $payload !== null)
		{
			$data = json_encode($payload);

			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);

			$this->headers->set('Content-Length', strval(strlen($data)));
		}

		$this->setOptions([
			CURLOPT_CUSTOMREQUEST => $this->method->asString(),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $this->headers->getAll(true)
		]);

		$response = curl_exec($this->curl);
		$this->info = curl_getinfo($this->curl);

		curl_close($this->curl);

		if (!json_validate($response))
		{
			throw new Exceptions\HttpException('Invalid JSON response.');
		}

		return json_decode($response);
	}

}
