# Haku\Errors

Centralized error handling and logging system for Haku. This package provides environment-aware error reporting, custom error and exception handlers, and structured logging capabilities.

---

## Overview

The Errors package converts PHP errors to exceptions, provides environment-specific error reporting configurations, and logs errors to file with timestamps and context information.

### Features

- **Environment-Aware Error Reporting** — Different error display settings for dev/test/production
- **Error to Exception Conversion** — Converts PHP errors to `ErrorException` for consistent handling
- **Structured Logging** — Logs errors with timestamps, environment context, and severity levels
- **Exception Handling** — Automatic exception logging with stack traces in development

---

## Functions

### `Haku\Errors\initialize()`

Initializes the complete error handling system for the specified environment.

```php
use function Haku\Errors\initialize;

// Initialize for development
initialize('dev');

// Initialize for production (default)
initialize('production');
```

**Parameters:**
- `$environment` (string) — Environment name: 'dev', 'test', or 'production' (default: 'production')

**What it does:**
1. Configures error reporting based on environment
2. Registers custom error handler
3. Registers custom exception handler

> [!IMPORTANT]
> Call `initialize()` early in your bootstrap process, before any other code that might generate errors.

---

### `Haku\Errors\configureErrorReporting()`

Configures PHP's error reporting settings based on the environment.

```php
use function Haku\Errors\configureErrorReporting;

configureErrorReporting('dev');
```

**Environment Behaviors:**

**Development (`dev`/`development`)**
- Reports all errors (`E_ALL`)
- Displays errors in output
- Shows startup errors

**Testing (`test`/`testing`)**
- Reports all errors (`E_ALL`)
- Hides error display (logs instead)
- Enables error logging

**Production (`prod`/`production`)**
- Reports most errors (excludes `E_DEPRECATED` and `E_STRICT`)
- Hides error display
- Enables error logging

```php
// Development - see all errors immediately
configureErrorReporting('dev');
// E_ALL, display_errors=1, display_startup_errors=1

// Production - log errors, don't show them
configureErrorReporting('production');
// E_ALL & ~E_DEPRECATED & ~E_STRICT, display_errors=0, log_errors=1
```

---

### `Haku\Errors\handleError()`

Custom error handler that converts PHP errors to `ErrorException` instances.

```php
use function Haku\Errors\handleError;

// Manually invoke (usually automatic when registered)
handleError(E_WARNING, 'Division by zero', '/path/to/file.php', 42);
```

**Parameters:**
- `$severity` (int) — Error severity level
- `$message` (string) — Error message
- `$file` (string) — File where error occurred
- `$line` (int) — Line number where error occurred

**Returns:** `bool` — False if error should not be handled, otherwise throws exception

**Behavior:**
- Respects `error_reporting()` settings
- Converts errors to `ErrorException` for consistent exception handling
- Allows errors to be caught with try/catch blocks

```php
try
{
    // This would normally be a warning
    $result = 10 / 0;
}
catch (ErrorException $e)
{
    echo "Caught error: " . $e->getMessage();
}
```

---

### `Haku\Errors\registerErrorHandler()`

Registers the custom error handler with PHP.

```php
use function Haku\Errors\registerErrorHandler;

registerErrorHandler();
```

After registration, all PHP errors matching `error_reporting()` settings will be converted to `ErrorException` instances.

---

### `Haku\Errors\handleException()`

Custom exception handler that logs uncaught exceptions.

```php
use function Haku\Errors\handleException;

// Manually invoke (usually automatic when registered)
handleException(new \Exception('Something went wrong'));
```

**Parameters:**
- `$exception` (\Throwable) — The exception to handle

**Behavior:**
- Logs exception class, message, file, and line number
- Includes stack trace in dev/test environments
- Uses structured log format

**Log Format:**
```
ExceptionClass: Message in /path/to/file.php:123
```

---

### `Haku\Errors\registerExceptionHandler()`

Registers the custom exception handler with PHP.

```php
use function Haku\Errors\registerExceptionHandler;

registerExceptionHandler();
```

After registration, all uncaught exceptions will be automatically logged.

---

### `Haku\Errors\logError()`

Logs error messages to file with timestamp and context.

```php
use function Haku\Errors\logError;

// Log an error
logError('Database connection failed');

// Log with specific level
logError('Deprecation warning', 'warning');
logError('Debug information', 'debug');
```

**Parameters:**
- `$message` (string) — Error message to log
- `$level` (string) — Log level (default: 'error')

**Log Location:**
- File: `private/logs/error.log`
- Directory created automatically if it doesn't exist

**Log Entry Format:**
```
[2025-10-14 10:30:45] [dev] [ERROR] Database connection failed
[2025-10-14 10:31:12] [production] [WARNING] Deprecation warning
```

**Components:**
- Timestamp in `Y-m-d H:i:s` format
- Environment (from `HAKU_ENV` constant)
- Log level (uppercase)
- Message content

---

## Usage Examples

### Basic Setup

```php
// bootstrap.php
use function Haku\Errors\initialize;

define('HAKU_ENV', 'dev');

// Initialize complete error handling
initialize(HAKU_ENV);
```

### Manual Error Logging

```php
use function Haku\Errors\logError;

try
{
    $db->connect();
}
catch (\Exception $e)
{
    logError(
        sprintf('Database connection failed: %s', $e->getMessage()),
        'error'
    );

    // Re-throw or handle gracefully
    throw $e;
}
```

### Custom Error Handler

```php
use function Haku\Errors\{
    registerErrorHandler,
    registerExceptionHandler
};

// Register handlers individually
registerErrorHandler();
registerExceptionHandler();

// Now all errors become exceptions
try
{
    // This warning becomes an ErrorException
    strlen(null);
}
catch (ErrorException $e)
{
    echo "Caught: " . $e->getMessage();
}
```

### Environment-Specific Handling

```php
use function Haku\Errors\{initialize, logError};

initialize($_ENV['APP_ENV'] ?? 'production');

try
{
    performRiskyOperation();
}
catch (\Throwable $e)
{
    // Log the error
    logError($e->getMessage(), 'error');

    // In development, show detailed error
    if (defined('HAKU_ENV') && HAKU_ENV === 'dev')
    {
        throw $e;
    }

    // In production, show friendly message
    echo json_encode([
        'error' => 'An error occurred. Please try again later.'
    ]);
}
```

---

## Error Log Management

### Log File Location

Errors are logged to: `private/logs/error.log`

### Reading Logs

```bash
# View recent errors
tail -f private/logs/error.log

# Search for specific errors
grep "ERROR" private/logs/error.log

# Filter by environment
grep "\[production\]" private/logs/error.log
```

### Log Rotation

> [!TIP]
> Implement log rotation to prevent error.log from growing too large. Consider using tools like `logrotate` or implementing custom rotation logic.

---

## Best Practices

**Initialize Early**
Always initialize error handling at the very start of your application:

```php
// First thing in bootstrap.php
use function Haku\Errors\initialize;

initialize(HAKU_ENV);
```

**Use Appropriate Log Levels**
- `error` — Serious problems that need attention
- `warning` — Potential issues that don't stop execution
- `debug` — Development/debugging information
- `info` — General informational messages

**Don't Expose Errors in Production**
Let the error handler manage error display:

```php
// ✓ Good - use error handler
initialize('production');

// ✗ Bad - manually displaying errors
if (HAKU_ENV === 'production')
{
    error_reporting(0);  // Don't do this
}
```

**Monitor Log Files**
Regularly check error logs in production to catch issues early.

---

## See also

- [[Haku\Exceptions]] — Framework exception classes
- [[Haku\Http\Exceptions]] — HTTP-specific exceptions (StatusException)
- [[Haku\Console]] — CLI error output formatting
