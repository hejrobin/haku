<?php
declare(strict_types=1);

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
};

use function Haku\Client\{
	detectClientOperatingSystem,
	detectClientBrowser,
	detectClientDevice,
};

spec('Client/Detection', function()
{

	describe('detectClientOperatingSystem', function()
	{

		it('detects Windows operating system', function()
		{
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
			return expect(detectClientOperatingSystem($userAgent))->toBe('Windows');
		});

		it('detects macOS operating system', function()
		{
			$userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36';
			return expect(detectClientOperatingSystem($userAgent))->toBe('macOS');
		});

		it('detects iOS operating system from iPhone', function()
		{
			$userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15';
			return expect(detectClientOperatingSystem($userAgent))->toBe('iOS');
		});

		it('detects iOS operating system from iPad', function()
		{
			$userAgent = 'Mozilla/5.0 (iPad; CPU OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15';
			return expect(detectClientOperatingSystem($userAgent))->toBe('iPadOS');
		});

		it('detects Android operating system', function()
		{
			$userAgent = 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36';
			return expect(detectClientOperatingSystem($userAgent))->toBe('Android');
		});

		it('detects Linux operating system', function()
		{
			$userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36';
			return expect(detectClientOperatingSystem($userAgent))->toBe('Linux');
		});

		it('returns Unknown for unrecognized operating system', function()
		{
			$userAgent = 'Mozilla/5.0 (FreeBSD; i386) AppleWebKit/537.36';
			return expect(detectClientOperatingSystem($userAgent))->toBe('Unknown');
		});

		it('handles case-insensitive matching', function()
		{
			$userAgent = 'MOZILLA/5.0 (WINDOWS NT 10.0; WIN64; X64) APPLEWEBKIT/537.36';
			return expect(detectClientOperatingSystem($userAgent))->toBe('Windows');
		});

		it('handles empty user agent', function()
		{
			return expect(detectClientOperatingSystem(''))->toBe('Unknown');
		});

	});

	describe('detectClientBrowser', function()
	{

		it('detects Firefox browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0';
			return expect(detectClientBrowser($userAgent))->toBe('Firefox');
		});

		it('detects Edge browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Edg/92.0.902.67';
			return expect(detectClientBrowser($userAgent))->toBe('Edge');
		});

		it('detects Brave browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Brave Chrome/92.0.4515.131 Safari/537.36';
			return expect(detectClientBrowser($userAgent))->toBe('Brave');
		});

		it('detects Chrome browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36';
			return expect(detectClientBrowser($userAgent))->toBe('Chrome');
		});

		it('detects Opera browser with opr', function()
		{
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36 OPR/78.0.4093.112';
			return expect(detectClientBrowser($userAgent))->toBe('Opera');
		});

		it('detects Opera browser with opera', function()
		{
			$userAgent = 'Opera/9.80 (Windows NT 6.1; WOW64) Presto/2.12.388 Version/12.18';
			return expect(detectClientBrowser($userAgent))->toBe('Opera');
		});

		it('detects Vivaldi browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Vivaldi/4.1';
			return expect(detectClientBrowser($userAgent))->toBe('Vivaldi');
		});

		it('detects Safari browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15';
			return expect(detectClientBrowser($userAgent))->toBe('Safari');
		});

		it('detects Tor browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; rv:78.0) Gecko/20100101 Firefox/78.0 TorBrowser/10.5.2';
			return expect(detectClientBrowser($userAgent))->toBe('Tor');
		});

		it('detects DuckDuckGo browser', function()
		{
			$userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15 DuckDuckGo/7';
			return expect(detectClientBrowser($userAgent))->toBe('DuckDuckGo');
		});

		it('detects Arc browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.75 Safari/537.36 Arc/1.0';
			return expect(detectClientBrowser($userAgent))->toBe('Arc');
		});

		it('returns Unknown for unrecognized browser', function()
		{
			$userAgent = 'CustomBrowser/1.0';
			return expect(detectClientBrowser($userAgent))->toBe('Unknown');
		});

		it('detects browser before Chrome when both present', function()
		{
			// Edge should be detected before Chrome since it contains 'chrome' in UA
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/92.0.4515.131 Edg/92.0.902.67';
			return expect(detectClientBrowser($userAgent))->toBe('Edge');
		});

		it('handles case-insensitive matching', function()
		{
			$userAgent = 'MOZILLA/5.0 (WINDOWS NT 10.0; WIN64; X64; RV:91.0) GECKO/20100101 FIREFOX/91.0';
			return expect(detectClientBrowser($userAgent))->toBe('Firefox');
		});

		it('handles empty user agent', function()
		{
			return expect(detectClientBrowser(''))->toBe('Unknown');
		});

	});

	describe('detectClientDevice', function()
	{

		it('detects mobile device from mobile keyword', function()
		{
			$userAgent = 'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Mobile Safari/537.36';
			return expect(detectClientDevice($userAgent))->toBe('Mobile');
		});

		it('detects mobile device from iPhone', function()
		{
			$userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15';
			return expect(detectClientDevice($userAgent))->toBe('Mobile');
		});

		it('detects mobile device from Android', function()
		{
			$userAgent = 'Mozilla/5.0 (Linux; Android 11) AppleWebKit/537.36';
			return expect(detectClientDevice($userAgent))->toBe('Mobile');
		});

		it('detects tablet device from iPad', function()
		{
			$userAgent = 'Mozilla/5.0 (iPad; CPU OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15';

			var_dump($userAgent);

			return expect(detectClientDevice($userAgent))->toBe('Tablet');
		});

		it('detects tablet device from tablet keyword', function()
		{
			$userAgent = 'Mozilla/5.0 (Linux; Android 11; SM-T870) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Tablet';
			return expect(detectClientDevice($userAgent))->toBe('Tablet');
		});

		it('defaults to Desktop for standard desktop browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36';
			return expect(detectClientDevice($userAgent))->toBe('Desktop');
		});

		it('defaults to Desktop for macOS browser', function()
		{
			$userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15';
			return expect(detectClientDevice($userAgent))->toBe('Desktop');
		});

		it('defaults to Desktop for Linux browser', function()
		{
			$userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36';
			return expect(detectClientDevice($userAgent))->toBe('Desktop');
		});

		it('handles case-insensitive matching', function()
		{
			$userAgent = 'MOZILLA/5.0 (IPHONE; CPU IPHONE OS 14_7_1 LIKE MAC OS X)';
			return expect(detectClientDevice($userAgent))->toBe('Mobile');
		});

		it('handles empty user agent', function()
		{
			return expect(detectClientDevice(''))->toBe('Desktop');
		});

	});

});
