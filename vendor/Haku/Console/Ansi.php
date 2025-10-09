<?php
declare(strict_types=1);

namespace Haku\Console;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

enum Ansi: int
{
	case Off = 0;

	// Formatting
	case Bold = 1;
	case Italic = 3;
	case Underline = 4;
	case Blink = 5;
	case Inverse = 7;
	case Hidden = 8;

	// Foreground Color
	case Black = 30;
	case Red = 31;
	case Green = 32;
	case Yellow = 33;
	case Blue = 34;
	case Magenta = 35;
	case Cyan = 36;
	case White = 37;

	// Background Color
	case BlackBackground = 40;
	case RedBackground = 41;
	case GreenBackground = 42;
	case YellowBackground = 43;
	case BlueBackground = 44;
	case MagentaBackground = 45;
	case CyanBackground = 46;
	case WhiteBackground = 47;

	public static function openTag(): string
	{
		return "\033[";
	}

	public static function closeTag(): string
	{
		return 'm';
	}
}

