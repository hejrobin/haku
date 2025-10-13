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

use Haku\Console\Ansi;

spec('Console/Ansi', function()
{

	describe('ANSI tag formatting', function()
	{

		it('returns correct open tag', function()
		{
			return expect(Ansi::openTag())->toEqual("\033[");
		});

		it('returns correct close tag', function()
		{
			return expect(Ansi::closeTag())->toEqual('m');
		});

	});

	describe('Formatting codes', function()
	{

		it('has correct Off code', function()
		{
			return expect(Ansi::Off->value)->toBe(0);
		});

		it('has correct Bold code', function()
		{
			return expect(Ansi::Bold->value)->toBe(1);
		});

		it('has correct Italic code', function()
		{
			return expect(Ansi::Italic->value)->toBe(3);
		});

		it('has correct Underline code', function()
		{
			return expect(Ansi::Underline->value)->toBe(4);
		});

		it('has correct Blink code', function()
		{
			return expect(Ansi::Blink->value)->toBe(5);
		});

		it('has correct Inverse code', function()
		{
			return expect(Ansi::Inverse->value)->toBe(7);
		});

		it('has correct Hidden code', function()
		{
			return expect(Ansi::Hidden->value)->toBe(8);
		});

	});

	describe('Foreground colors', function()
	{

		it('has correct Black code', function()
		{
			return expect(Ansi::Black->value)->toBe(30);
		});

		it('has correct Red code', function()
		{
			return expect(Ansi::Red->value)->toBe(31);
		});

		it('has correct Green code', function()
		{
			return expect(Ansi::Green->value)->toBe(32);
		});

		it('has correct Yellow code', function()
		{
			return expect(Ansi::Yellow->value)->toBe(33);
		});

		it('has correct Blue code', function()
		{
			return expect(Ansi::Blue->value)->toBe(34);
		});

		it('has correct Magenta code', function()
		{
			return expect(Ansi::Magenta->value)->toBe(35);
		});

		it('has correct Cyan code', function()
		{
			return expect(Ansi::Cyan->value)->toBe(36);
		});

		it('has correct White code', function()
		{
			return expect(Ansi::White->value)->toBe(37);
		});

	});

	describe('Background colors', function()
	{

		it('has correct BlackBackground code', function()
		{
			return expect(Ansi::BlackBackground->value)->toBe(40);
		});

		it('has correct RedBackground code', function()
		{
			return expect(Ansi::RedBackground->value)->toBe(41);
		});

		it('has correct GreenBackground code', function()
		{
			return expect(Ansi::GreenBackground->value)->toBe(42);
		});

		it('has correct YellowBackground code', function()
		{
			return expect(Ansi::YellowBackground->value)->toBe(43);
		});

		it('has correct BlueBackground code', function()
		{
			return expect(Ansi::BlueBackground->value)->toBe(44);
		});

		it('has correct MagentaBackground code', function()
		{
			return expect(Ansi::MagentaBackground->value)->toBe(45);
		});

		it('has correct CyanBackground code', function()
		{
			return expect(Ansi::CyanBackground->value)->toBe(46);
		});

		it('has correct WhiteBackground code', function()
		{
			return expect(Ansi::WhiteBackground->value)->toBe(47);
		});

	});

});
