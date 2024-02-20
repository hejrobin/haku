<?php
declare(strict_types=1);

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
	expectAll,
};

use Haku\Jwt\{
	Token,
	Algorithm,
};

use Haku\Jwt\Exceptions\TokenException;
use Haku\Jwt\Exceptions\IntegrityException;

spec('Jwt\Token', function()
{

	describe('HS256 Algorithm', function ()
	{

		it('can create HS256 token', function ()
		{
			if (!defined('HAKU_JWT_SIGNING_KEY'))
			{
				return false;
			}

			$token = new Token(Algorithm::HS256);

			return expect([$token, 'encode'])
				->withArguments([Algorithm::HS256, \HAKU_JWT_SIGNING_KEY])
				->not()->ToThrow(TokenException::class);
		});

		it('throws exception on algorithm mismatch when encoding', function ()
		{
			if (!defined('HAKU_JWT_SIGNING_KEY'))
			{
				return false;
			}

			$token = new Token(Algorithm::HS256);

			return expect([$token, 'encode'])
				->withArguments([Algorithm::HS384, \HAKU_JWT_SIGNING_KEY])
				->toThrow();
		});

		it('throws exception when trying to encode expired token', function ()
		{
			if (!defined('HAKU_JWT_SIGNING_KEY'))
			{
				return false;
			}

			$token = new Token(Algorithm::HS256);

			$time = time();
			$token->issuedAt($time - 2000);
			$token->expiresAt($time - 1000);

			return expect([$token, 'encode'])
				->withArguments([Algorithm::HS256, \HAKU_JWT_SIGNING_KEY])
				->ToThrow(IntegrityException::class);
		});

	});

});
