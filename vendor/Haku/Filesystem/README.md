# Haku\Filesystem

Filesystem operations and utilities for Haku. This package provides file and directory manipulation, archiving (ZIP), and storage services.

---

## Overview

The Filesystem package includes:
- **File Operations** — Creating, reading, writing, deleting files and directories
- **Archiver** — ZIP archive creation and extraction
- **StorageService** — Unified storage interface for file management

---

## Haku\Filesystem\Archiver

ZIP archive manipulation using PHP's `ZipArchive` class.

### Basic Usage

```php
use Haku\Filesystem\Archiver;

$zip = new Archiver();

// Open existing archive or create new one
$zip->open('/path/to/archive.zip');

// Add files
$zip->addFile('/path/to/file.txt', 'file.txt');

// Extract all
$zip->extractTo('/destination/path');

// Extract specific files
$zip->extractTo('/destination', ['file1.txt', 'file2.txt']);

// Close archive
$zip->close();
```

### Common Operations

```php
// Create archive with multiple files
$zip = new Archiver();
$zip->open('/path/to/backup.zip', \ZipArchive::CREATE);
$zip->addFile('/path/to/config.php', 'config.php');
$zip->addFile('/path/to/data.json', 'data.json');
$zip->close();

// Extract directory from archive
$zip = new Archiver();
$zip->open('/path/to/archive.zip');
$zip->extractDirectoryTo('/destination', 'vendor/Haku');
$zip->close();
```

---

## Haku\Filesystem\StorageService

Unified interface for file storage operations.

### Basic Usage

```php
use Haku\Filesystem\StorageService;

$storage = new StorageService('/base/path');

// Write file
$storage->put('file.txt', 'content');

// Read file
$content = $storage->get('file.txt');

// Check if file exists
if ($storage->exists('file.txt')) {
    // ...
}

// Delete file
$storage->delete('file.txt');
```

---

## Helper Functions

### Directory Operations

```php
use function Haku\Filesystem\{
    createDirectory,
    deleteDirectory,
    copyDirectory
};

// Create directory recursively
createDirectory('/path/to/dir', 0755);

// Delete directory and contents
deleteDirectory('/path/to/dir');

// Copy directory recursively
copyDirectory('/source', '/destination');
```

### File Operations

```php
use function Haku\Filesystem\{
    readFile,
    writeFile,
    appendFile,
    deleteFile
};

// Read file contents
$content = readFile('/path/to/file.txt');

// Write to file (overwrites)
writeFile('/path/to/file.txt', 'content');

// Append to file
appendFile('/path/to/log.txt', "Log entry\n");

// Delete file
deleteFile('/path/to/file.txt');
```

---

## Usage Examples

### Backup Application Files

```php
use Haku\Filesystem\Archiver;
use function Haku\resolvePath;

$zip = new Archiver();
$backupPath = resolvePath('private/backups/backup-' . date('Y-m-d') . '.zip');

$zip->open($backupPath, \ZipArchive::CREATE);
$zip->addFile(resolvePath('config.php'), 'config.php');
$zip->addFile(resolvePath('bootstrap.php'), 'bootstrap.php');
$zip->close();
```

### Extract Uploaded ZIP

```php
use Haku\Filesystem\Archiver;
use function Haku\resolvePath;

if ($_FILES['upload']['type'] === 'application/zip') {
    $zip = new Archiver();
    $zip->open($_FILES['upload']['tmp_name']);
    $zip->extractTo(resolvePath('private/uploads'));
    $zip->close();
}
```

---

## See also

- [[Haku\Console]] — Uses Filesystem for code generation and file operations
- [[Haku\Errors]] — Uses Filesystem for log file management
