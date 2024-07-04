<?php
declare(strict_types=1);

namespace Haku\Filesystem;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use ZipArchive;

class Archiver extends ZipArchive
{

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
