<?php
declare(strict_types=1);

namespace Haku\Filesystem;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

abstract class StorageService
{

	abstract public function getStoragePath(int $resourceId): string;

	abstract public function getResourceName(): string;

	abstract public function getAllowedMimeTypes(): array;

	abstract public function getMaxFileSize(): int;

	abstract protected function prepareStorage(int $resourceId): bool;

	abstract protected function moveToStorage(array $file): bool;

	protected function getMimeType(array $file): string
	{
		if (file_exists($file['tmp_name']))
		{
			return mime_content_type($file['tmp_name']) ?? $file['type'];
		}

		return $file['type'];
	}

	protected function generateGuid(): string
	{
		return bin2hex(random_bytes(16));
	}

	protected function extensionFromMime(string $mime): string
	{
		return match ($mime) {
			'image/jpeg' => 'jpg',
			'image/png' => 'png',
			'image/webp' => 'webp',
			'application/pdf' => 'pdf',
			default => 'bin',
		};
	}

	protected function generateFilename(array $file, string $guid): string
	{
		$mime = $this->getMimeType($file);
		$ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: $this->extensionFromMime($mime);

		return "{$guid}.{$ext}";
	}

	protected function getUploadError(array $file): ?string
	{
		if ($file['error'] === UPLOAD_ERR_OK)
		{
			return null;
		}

		return match ($file['error'])
		{
			UPLOAD_ERR_INI_SIZE => 'file size too big (server limit)',
			UPLOAD_ERR_FORM_SIZE => 'file size too big (form limit)',
			UPLOAD_ERR_PARTIAL => 'recieved partial file',
			UPLOAD_ERR_NO_FILE => 'no file present',
			UPLOAD_ERR_NO_TMP_DIR => 'upload folder does not exist',
			UPLOAD_ERR_CANT_WRITE => 'failed to write file to disk',
			UPLOAD_ERR_EXTENSION => 'upload blocked by extension',
			default => 'unknown error during upload',
		};
	}

	protected function validateUploadedFile(string $filename): bool
	{
		return is_uploaded_file($filename);
	}

	protected function getFileMimeType(string $filename): string
	{
		return mime_content_type($filename);
	}

	protected function processFile(array $file): array
	{
		$guid = $this->generateGuid();
		$name = $file['name'];
		$size = $file['size'];
		$mime = $this->getMimeType($file);
		$fileName = $this->generateFilename($file, $guid);

		$processed = [
			'guid' => $guid,
			'name' => $name,
			'mime' => $mime,
			'size' => $size,
			'file' => $fileName,
			'error' => $this->getUploadError($file),
		];

		if ($file['error'] !== UPLOAD_ERR_OK)
		{
			$processed['uploaded'] = false;

			return $processed;
		}

		if ($size > $this->getMaxFileSize())
		{
			$processed['uploaded'] = false;
			$processed['error'] = 'file size too big';

			return $processed;
		}

		if (!in_array($mime, $this->getAllowedMimeTypes()))
		{
			$processed['uploaded'] = false;
			$processed['error'] = 'mime type not allowed';

			return $processed;
		}

		$processed['uploaded'] = $this->moveToStorage($file);

		return $processed;
	}

	public function upload(int $resourceId, array $files): array
	{
		$stored = [];

		$this->prepareStorage($resourceId);

		foreach ($files as $file)
		{
			$stored[] = $this->processFile($file);
		}

		return $stored;
	}

}
