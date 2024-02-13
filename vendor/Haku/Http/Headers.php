<?php
declare(strict_types=1);

namespace Haku\Http;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

class Headers
{

	protected array $standardizedHeaders = [
		'Accept-Charset',
		'Accept-Datetime',
		'Accept-Encoding',
		'Accept-Language',
		'Accept-Patch',
		'Accept-Ranges',
		'Accept',
		'Access-Control-Allow-Credentials',
		'Access-Control-Allow-Headers',
		'Access-Control-Allow-Methods',
		'Access-Control-Allow-Origin',
		'Access-Control-Expose-Headers',
		'Access-Control-Max-Age',
		'Access-Control-Request-Headers',
		'Access-Control-Request-Method',
		'Age',
		'Allow',
		'Authorization',
		'Cache-Control',
		'Connection',
		'Content-Disposition',
		'Content-Encoding',
		'Content-Language',
		'Content-Length',
		'Content-Range',
		'Content-Security-Policy',
		'Content-Type',
		'Cookie',
		'Date',
		'Delta-Base',
		'Etag',
		'Expect',
		'Expires',
		'Forwarded',
		'From',
		'Host',
		'HTTP2-Settings',
		'If-Match',
		'If-Modified-Since',
		'If-None-Match',
		'If-Range',
		'If-Unmodified-Since',
		'Im',
		'Last-Modified',
		'Location',
		'Max-Forwards',
		'Origin',
		'Pragma',
		'Prefer',
		'Preference-Applied',
		'Proxy-Authenticate',
		'Proxy-Authorization',
		'Public-Key-Pins',
		'Range',
		'Referer',
		'Retry-After',
		'Server',
		'Set-Cookie',
		'Strict-Transport-Security',
		'Te',
		'Trailer',
		'Transfer-Encoding',
		'Transfer-Encoding',
		'Upgrade',
		'Upgrade',
		'User-Agent',
		'Vary',
		'Via',
		'Warning',
		'Www-Authenticate',
	];

	protected array $nonStandardizedHeaders = [
		'Dnt',
		'Front-End-Https',
		'Refresh',
		'Report-To',
		'Save-Data',
		'Timing-Allow-Origin',
		'Upgrade-Insecure-Requests',
	];

	protected array $headerAliases = [
		'Dnt' => 'DNT',
		'Etag' => 'ETag',
		'Http2-Settings' => 'HTTP2-Settings',
		'Im' => 'IM',
		'Te' => 'TE',
		'Www-Authenticate' => 'WWW-Authenticate',
		'X-Xss-Protection' => 'X-XSS-Protection',
	];

	protected array $headers = [];

	public function __construct(
		array $initialHeaders = [],
		private Status $status = Status::OK
	) {
		$this->append($initialHeaders);
	}

	/**
	 *	Normalizes HTTP header name to always have a capitalized first letter in every word, and be properly hyphenated.
	 *	Non-valid headers will be prepended with an X.
	 *
	 *	I.e. "content type" into "Content-Type" and "custom header" into "X-Custom-Header".
	 */
	protected function name(string $header): string
	{
		$normalized = ucwords(str_replace(' ', '-', strtolower($header)), '-');

		if (
			!in_array($normalized, $this->standardizedHeaders) &&
			!in_array($normalized, $this->nonStandardizedHeaders)
		) {
			$normalized = "X-{$normalized}";
		}

		if (array_key_exists($normalized, $this->headerAliases))
		{
			return $this->headerAliases[$normalized];
		}

		return $normalized;
	}

	/**
	 *	Validates the existance of a header, but not it's value.
	 */
	public function has(string $header): bool
	{
		return array_key_exists($this->name($header), $this->headers);
	}

	/**
	 *	Adds or overwrites a header.
	 */
	public function set(string $header, string $value): void
	{
		if (strlen($value) > 0)
		{
			$this->headers[$this->name($header)] = $value;
		}
	}

	/**
	 *	Adds or overwrites several headers.
	 */
	public function append(array $headers): void
	{
		foreach ($headers as $header => $value)
		{
			if (is_string($header) && is_string($value))
			{
				$this->set($header, $value);
			}
		}
	}

	/**
	 *	Returns a set header.
	 */
	public function get(string $header): string | null
	{
		if ($this->has($header))
		{
			return $this->headers[$this->name($header)];
		}

		return null;
	}

	/**
	 *	Removes a header.
	 */
	public function remove(string $header): void
	{
		if ($this->has($header))
		{
			unset($this->headers[$this->name($header)]);
		}
	}

	public function getAll(): array
	{
		return $this->headers;
	}

	/**
	 *	Flushes *all* set headers of current instance.
	 */
	public function flush(): void
	{
		$this->headers = [];
	}

	/**
	 *	Sets HTTP status
	 */
	public function status(Status $status)
	{
		$this->status = $status;
	}

	public function getStatus(): Status
	{
		return $this->status;
	}

	/**
	 *	Sends HTTP headers to the client.
	 */
	public function send(): void
	{
		header($this->status->asString());

		foreach ($this->headers as $header => $value)
		{
			header("{$header}: {$value}");
		}
	}
}
