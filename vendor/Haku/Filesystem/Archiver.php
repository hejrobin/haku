<?php
declare(strict_types=1);

namespace Haku\Filesystem;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use ZipArchive;

/**
 *	This class works with zip files
 */
class Archiver extends ZipArchive
{

	/**
	 *	Adds a directory and all its contents to the archive.
	 *
	 *	@param string $path Path to the directory to add
	 *	@param string $localName Name in the archive (optional)
	 *
	 *	@return bool True on success, false on failure
	 */
	public function addDirectory(string $path, ?string $localName = null): bool
	{
		if (!is_dir($path))
		{
			return false;
		}

		$localName = $localName ?? basename($path);

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
			\RecursiveIteratorIterator::SELF_FIRST
		);

		foreach ($iterator as $item)
		{
			$itemPath = $item->getPathname();
			$relativePath = $localName . DIRECTORY_SEPARATOR . substr($itemPath, strlen($path) + 1);

			if ($item->isDir())
			{
				$this->addEmptyDir($relativePath);
			}
			else
			{
				if (!$this->addFile($itemPath, $relativePath))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 *	Extracts contents of a zip file to a target directory.
	 */
	public function extractDirectoryTo(string $target, string $source): array
	{
		$errors = [];

		$target = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $target);
		$source = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $source);

		if (\str_ends_with($target, DIRECTORY_SEPARATOR) === false)
		{
			$target .= DIRECTORY_SEPARATOR;
		}

		if (\str_ends_with($source, '/') === false)
		{
			$source .= '/';
		}

		for ($n = 0; $n < $this->numFiles; $n++)
		{
			$fileName = $this->getNameIndex($n);

			if (substr($fileName, 0, mb_strlen($source, "UTF-8")) === $source)
			{
				$relativePath = substr($fileName, mb_strlen($source, "UTF-8"));
				$relativePath = str_replace(["/", "\\"], DIRECTORY_SEPARATOR, $relativePath);

				if (mb_strlen($relativePath, "UTF-8") > 0)
				{
					if (substr($fileName, -1) === "/")
					{
						$directory = $target . $relativePath;

						if (!is_dir($directory) && !@mkdir($directory, 0755, true))
						{
							$errors[$n] = $fileName;
						}
					}
					else
					{
						if (dirname($relativePath) !== '.')
						{
							$directory = $target . dirname($relativePath);

							if (!is_dir($directory))
							{
								@mkdir($directory, 0755, true);
							}
						}

						if (@file_put_contents($target . $relativePath, $this->getFromIndex($n)) === false)
						{
							$errors[$n] = $fileName;
						}
					}
				}
			}
		}

		return $errors;
	}

}
