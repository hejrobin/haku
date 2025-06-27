<?php
declare(strict_types=1);
use function Haku\Filesystem\getUploadedFiles;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') === false) exit;

use function Haku\Spec\{
	spec,
	describe,
	it,
	expect,
	expectAll,
};

use Haku\Filesystem\StorageService;

class StubStorageService extends StorageService
{

	public function getStoragePath(int $resourceId): string
	{
		return '/';
	}

	public function getResourceName(): string
	{
		return 'stub';
	}

	public function getAllowedMimeTypes(): array
	{
		return ['text/plain'];
	}

	public function getMaxFileSize(): int
	{
		return 5 * 1024 * 1024;
	}

	protected function prepareStorage(int $resourceId): bool
	{
		return true;
	}

	protected function moveToStorage(array $file): bool
	{
		return true;
	}

}

function getMockedFilesPayload(): array
{
	return [
		'tmp_name' => ['/tmp/abc123.png', '/tmp/text.txt'],
		'name' => ['test.png', 'text.txt'],
		'size' => [123, 123456],
		'type' => ['image/png', 'text/plain'],
		'error' => [UPLOAD_ERR_PARTIAL, UPLOAD_ERR_OK],
	];
}

spec('Filesystem\StorageService', function()
{

	describe('File Storage', function()
	{

		it('can upload files', function()
		{
			$storage = new StubStorageService();

			$files = getUploadedFiles(getMockedFilesPayload());

			[$failed, $successful] =  $storage->upload(1337, $files);

			return expectAll(
				expect($successful['uploaded'])->toBeTrue(),
				expect($failed['uploaded'])->toBeFalse()
			);
		});

	});

});
