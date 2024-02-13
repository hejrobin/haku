<?php
declare(strict_types=1);

namespace Haku\Http;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

enum Status: int
{

	// Type: Success
	case OK = 200;
	case Created = 201;
	case Accepted = 202;
	case NonAuthoritativeInformation = 203;
	case NoContent = 204;
	case ResetContent = 205;
	case PartialContent = 206;

	// Type: Redirect
	case MultipleChoices = 300;
	case MovedPermanently = 301;
	case Found = 302;
	case SeeOther = 303;
	case NotModified = 304;
	case UseProxy = 305;
	case TemporaryRedirect = 307;

	// Type: Client Error
	case BadRequest = 400;
	case Unauthorized = 401;
	case PaymentRequired = 402;
	case Forbidden = 403;
	case NotFound = 404;
	case MethodNotAllowed = 405;
	case NotAcceptable = 406;
	case ProxyAuthenticationRequired = 407;
	case RequestTimeout = 408;
	case Conflict = 409;
	case Gone = 410;
	case LengthRequired = 411;
	case PreconditionFailed = 412;
	case RequestEntityTooLarge = 413;
	case RequestURITooLong = 414;
	case UnsupportedMediaType = 415;
	case RequestedRangeNotSatisfiable = 416;
	case ExpectationFailed = 417;
	case ImATeapot = 418;
	case TooManyRequests = 429;

	// Type: Server Error
	case InternalServerError = 500;
	case NotImplemented = 501;
	case BadGateway = 502;
	case ServiceUnavailable = 503;
	case GatewayTimeout = 504;
	case HTTPVersionNotSupported = 505;
	case BandwidthLimitExceeded = 509;

	// Type: Developer Errors
	// @note Not all are included, go see {@link https://github.com/joho/7XX-rfc} for more.
	case Meh = 701;
	case IAmNotATeapot = 719;
	case KnownUnknowns = 721;
	case UnknownUnknowns = 722;
	case Tricky = 723;
	case FuckingUnicode = 732;
	case ComputerSaysNo = 740;
	case ConfoundedByPonies = 748;
	case ReservedForChuckNorris = 749;
	case UnderCaffeinated = 763;
	case OverCaffeinated = 764;
	case FurtherFundingRequired = 787;
	case DesignersFinalDesignsWerent = 788;
	case ZombieApocalypse = 793;

	public static function resolve(): self
	{
		return self::from(http_response_code());
	}

	public function getCode(): int
	{
		return $this->value;
	}

	public function getTypeNumber(): int
	{
		return intval(floor($this->getCode() / 100));
	}

	public function getType(): string
	{
		return match ($this->getTypeNumber())
		{
			2 => 'Success',
			3 => 'Redirect',
			4 => 'Client Error',
			5 => 'Server Error',
			7 => 'Developer Error'
		};
	}

	public function getName(): string
	{
		return match ($this)
		{
			static::NonAuthoritativeInformation => 'Non-Authorative Information',
			static::RequestURITooLong => 'Request URI Too Long',

			// @note We gotta laugh, right?
			static::FuckingUnicode => 'Fucking Unic💩de',
			static::DesignersFinalDesignsWerent => "Designers' Final Designs Weren't",
			static::ZombieApocalypse => 'Zombie Apocalypse 🧟‍♀️',

			default => trim(preg_replace('/[A-Z]([A-Z](?![a-z]))*/', ' $0', $this->name)),
		};
	}

	public function asString(): string
	{
		return sprintf(
			'%s %d %s',
			$_SERVER['SERVER_PROTOCOL'],
			$this->getCode(),
			$this->getName()
		);
	}
}
