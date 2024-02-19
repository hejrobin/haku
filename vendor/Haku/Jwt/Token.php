<?php
declare(strict_types=1);

namespace Haku\Jwt;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Jwt\validateTokenTimestamp;

use function Haku\Spl\Strings\{
	encodeBase64Url,
	decodeBase64Url
};

use Haku\Jwt\Exceptions\{
	TokenException,
	AlgorithmException,
	IntegrityException,
};


class Token
{

	protected array $header = [];

	protected array $payload = [];

	protected array $reservedKeys = [
		'iss',
		'sub',
		'aud',
		'exp',
		'nbf',
		'iat',
		'jti',
		'typ',
	];

	public function __construct(
		protected string $algorithm,
		protected int $timeOffset = 0,
	) {
		$this->header = [
			'typ' => 'JWT',
			'alg' => $algorithm,
		];

		$this->issuedAt(time());
	}

	public function issuedAt(int $issuedAt = null): int
	{
		if ($issuedAt === null)
		{
			return $this->get('iat');
		}
		elseif (validateTokenTimestamp($issuedAt))
		{
			$this->payload['iat'] = $issuedAt;

			return $issuedAt;
		}
		else
		{
			throw new TokenException('Invalid issuedAt timestamp.');
		}
	}

	public function expiresAt(int $expiresAt = null): int
	{
		if ($expiresAt === null)
		{
			return $this->get('exp');
		}
		elseif (validateTokenTimestamp($expiresAt))
		{
			$this->payload['exp'] = $expiresAt;

			return $expiresAt;
		}
		else
		{
			throw new TokenException('Invalid expire timestamp.');
		}
	}

	public function hasExpired(): bool
	{
		if (array_key_exists('exp', $this->payload))
		{
			return time() - $this->timeOffset >= $this->payload['exp'];
		}

		return false;
	}

	public function claimableAt(int $claimableAt = null): int {
		if ($claimableAt === null)
		{
			return $this->get('nbf');
		}
		elseif (validateTokenTimestamp($claimableAt))
		{
			$this->payload['nbf'] = $claimableAt;

			return $claimableAt;
		}
		else
		{
			throw new TokenException('Invalid "not before" timestamp.');
		}
	}

	public function isClaimable(): bool
	{
		if (array_key_exists('nbf', $this->payload))
		{
			return time() + $this->timeOffset >= $this->payload['nbf'];
		}

		return true;
	}

	public function setPayload(array $payload)
	{
		$this->payload = $payload;
	}

	public function getPayload(): array
	{
		return $this->payload;
	}

	public function set(string $key, mixed $value): self
	{
		if (in_array(strtolower($key), $this->reservedKeys))
		{
			throw new TokenException('Cannot alter reserved token keys.');
		}

		$this->payload[$key] = $value;

		return $this;
	}

	public function get(string $key): mixed
	{
		return $this->payload[$key] ?? null;
	}

	public function has(string $key): bool
	{
		return $this->get($key) !== null;
	}

	public function remove(string $key): self
	{
		if (in_array(strtolower($key), $this->reservedKeys))
		{
			throw new TokenException('Cannot alter reserved token keys.');
		}

		unset($this->payload[$key]);

		return $this;
	}

	protected function getEncodedHeader(): string
	{
		return encodeBase64Url(json_encode($this->header));
	}

	protected function getEncodedPayload(): string
	{
		return encodeBase64Url(json_encode($this->payload));
	}

	protected function getEncodedSignature(
		string $algorithm,
		string $signingKey
	): string
	{
		$alg = Algorithm::get($algorithm);

		if ($algorithm !== $this->header['alg'])
		{
			throw new IntegrityException('Token algorithm mismatch.');
		}

		switch ($alg->protocol)
		{
			case Algorithm::HMAC:
				try
				{
					$message = implode('.', [
						$this->getEncodedHeader(),
						$this->getEncodedPayload(),
					]);

					$signature = hash_hmac($alg->crypt, $message, $signingKey, false);
				}
				catch (\Exception $exception)
				{
					throw new TokenException('Could not generate token signature.');
				}
				finally
				{
					if ($signature === false) {
						throw new TokenException('Could not generate token signature.');
					}

					return $signature;
				}
				break;

			default:
				throw new TokenException('Could not get signature, invalid algorithm provided.');
				break;
		}
	}

	public function encode(string $algorithm, string $signingKey): string
	{
		if ($algorithm !== $this->algorithm)
		{
			throw new TokenException('Cannot encode token, algorithm mismatch.');
		}

		if ($this->hasExpired())
		{
			throw new IntegrityException('Attempt to encode claimed token.');
		}

		return implode('.', [
			$this->getEncodedHeader(),
			$this->getEncodedPayload(),
			$this->getEncodedSignature($this->algorithm, $signingKey),
		]);
	}

	public static function decode(
		string $token,
		string $algorithm,
		string $signingKey,
		int $timeOffset = 0,
	): self
	{
		$alg = Algorithm::get($algorithm);

		[$encodedHeader, $encodedPayload, $encodedSignature] = explode('.', $token);

		$encodedMessage = implode('.', [$encodedHeader, $encodedPayload]);

		switch ($alg->protocol)
		{
			case Algorithm::HMAC:
				$hash = hash_hmac($alg->crypt, $encodedMessage, $signingKey, false);

				if (hash_equals($hash, $encodedSignature) !== true) {
					throw new IntegrityException('Token integrity fail, invalid signature.');
				}
				break;
			default:
				throw new AlgorithmException('Unsupported algorithm provided.');
				break;
		}

		$decodedHeader = json_decode(decodeBase64Url($encodedHeader), true);
		$decodedPayload = json_decode(decodeBase64Url($encodedPayload), true);

		if (array_key_exists('alg', $decodedHeader) === false)
		{
			throw new IntegrityException('Token algorithm missing.');
		}

		if ($decodedHeader['alg'] !== $algorithm)
		{
			throw new IntegrityException('Token algorithm mismatch.');
		}

		$token = new Token($algorithm, $timeOffset);
		$token->setPayload($decodedPayload);

		return $token;
	}

}
