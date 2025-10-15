<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

use Haku\Console\Ansi;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;

use function Haku\manifest;

class Version extends Command
{

	private const REMOTE_MANIFEST_URL = 'https://raw.githubusercontent.com/hejrobin/haku/main/manifest.json';

	public function description(): string
	{
		return 'displays the current haku version';
	}

	public function options(): array
	{
		return [
			'--no-check|skip checking for updates from remote|',
		];
	}

	protected function fetchRemoteVersion(): ?string
	{
		$context = stream_context_create([
			'http' => [
				'timeout' => 3,
				'user_agent' => 'Haku-CLI',
			],
		]);

		$content = @file_get_contents(self::REMOTE_MANIFEST_URL, false, $context);

		if ($content === false)
		{
			return null;
		}

		$data = json_decode($content, true);

		return $data['version'] ?? null;
	}

	public function invoke(): bool
	{
		$pkg = manifest();
		$currentVersion = $pkg->version ?? '0.0.0';

		// Check remote version unless --no-check flag is provided
		$shouldSkipCheck = array_key_exists('no-check', $this->arguments->arguments);



		if (!$shouldSkipCheck)
		{
			$remoteVersion = $this->fetchRemoteVersion();

			if ($remoteVersion === null)
			{
				$this->output->error('could not fetch remote version');

				return false;
			}

			$remoteIsAhead = version_compare($remoteVersion, $currentVersion, '>');
			$localIsAhead = version_compare($remoteVersion, $currentVersion, '<');

			if ($remoteIsAhead)
			{
				$formattedRemoteVersion = $this->output->format($remoteVersion, Ansi::Green);
				$formattedLocalVersion = $this->output->format($currentVersion, Ansi::Yellow);

				$this->output->info(sprintf("local:  %s", $formattedLocalVersion));
				$this->output->info(sprintf("remote: %s", $formattedRemoteVersion));

				$this->output->break();
				$this->output->output("use 'haku upgrade' to download latest version", 'tip', Ansi::Green);
			}
			elseif ($localIsAhead)
			{
				$formattedRemoteVersion = $this->output->format($remoteVersion, Ansi::Yellow);
				$formattedLocalVersion = $this->output->format($currentVersion, Ansi::Green);

				$this->output->warn("local is ahead of remote");
				$this->output->break();

				$this->output->info(sprintf("local:  %s", $formattedLocalVersion));
				$this->output->info(sprintf("remote: %s", $formattedRemoteVersion));
			}
			else
			{
				$this->output->info(sprintf("local: %s", $currentVersion));
				$this->output->success('you are on the latest version');
			}
		}
		else
		{
			$this->output->info(sprintf("local:  %s", $currentVersion));
		}

		return true;
	}

}
