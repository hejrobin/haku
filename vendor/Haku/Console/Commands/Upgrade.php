<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use Haku\Console\Command;
use Haku\Filesystem\Archiver;

use function Haku\resolvePath;
use function Haku\Filesystem\deleteDirectory;

class Upgrade extends Command
{

	private string $identifier = 'haku-main';

	public function description(): string
	{
		return 'updates Haku framework from latest release';
	}

	private function source(): string
	{
		return 'https://github.com/hejrobin/haku/archive/main.zip';
	}

	private function target(): string
	{
		return resolvePath("private/{$this->identifier}.zip");
	}

	private function coreFiles(): array
	{
		return [
			"{$this->identifier}/bootstrap.php",
			"{$this->identifier}/index.php",
			"{$this->identifier}/manifest.json",
			"{$this->identifier}/LICENSE.md",
			"{$this->identifier}/README.md",
		];
	}

	protected function download(): void
	{
		$bytes = file_put_contents($this->target(), file_get_contents($this->source()));

		if ($bytes === 0)
		{
			$this->output->error('could not fetch repository');
		}
	}

	protected function extractSource(): void
	{
		if (file_exists($this->target()))
		{
			$zip = new Archiver();

			if ($zip->open($this->target()) === true)
			{
				$this->output->output('extracting source code...');

				// Firstly extract all the core files.
				$zip->extractTo(resolvePath('private'), $this->coreFiles());

				$errors = $zip->extractDirectoryTo(
					resolvePath("private/{$this->identifier}/private"),
					"{$this->identifier}/private"
				);

				$zip->extractDirectoryTo(
					resolvePath("private/{$this->identifier}/vendor"),
					"{$this->identifier}/vendor"
				);

				$zip->close();
			}
			else
			{
				$this->output->error('could not extract source code.');
			}
		}
	}

	protected function applyChanges(): void
	{
		$files = $this->coreFiles();

		foreach ($files as $file)
		{
			$source = resolvePath("private/{$file}");
			$target = resolvePath(basename($file));

			rename($source, $target);
		}

		deleteDirectory(resolvePath("private/generator-templates"));

		rename(
			resolvePath("private/{$this->identifier}/private/generator-templates"),
			resolvePath("private/generator-templates"),
		);

		deleteDirectory(resolvePath("vendor/Haku"));

		$source = resolvePath("private/{$this->identifier}/vendor/Haku");
		$target = resolvePath("vendor/Haku");

		rename($source, $target);
	}

	protected function cleanup(): void
	{
		$this->output->output('cleaning up temporary files...');

		deleteDirectory(resolvePath("private/{$this->identifier}"));
		unlink($this->target());
	}

	public function invoke(): bool
	{
		if (PHP_OS_FAMILY === 'Windows')
		{
			$this->output->error('windows support for this command is still pending...');

			return false;
		}

		$this->output->output('downloading latest haku files...');

		$this->download();
		$this->extractSource();

		$this->applyChanges();
		$this->cleanup();

		$this->output->success('done!');

		return true;
	}

}
