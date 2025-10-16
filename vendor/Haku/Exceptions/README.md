# Haku\Exceptions

Framework-specific exception classes for Haku. This package provides base exception types used throughout the Haku framework to distinguish between framework-level errors and vendor/third-party errors.

---

## Haku\Exceptions\FrameworkException

Base exception class for all Haku framework-related errors. Extends PHP's native `\Exception` class.

### Usage

Throw this exception when encountering framework-level errors such as configuration issues, initialization failures, or internal framework problems.

```php
use Haku\Exceptions\FrameworkException;

if (!file_exists($configPath))
{
	throw new FrameworkException('Configuration file not found');
}
```

### When to Use

Use `FrameworkException` for:
- Missing or invalid configuration
- Framework initialization errors
- Internal framework component failures
- Dependency injection issues
- Invalid framework state

> [!NOTE]
> `FrameworkException` is caught and handled by the framework's error handling system defined in Haku\Errors.

---

## Haku\Exceptions\VendorException

Base exception class for vendor/third-party library errors. Extends PHP's native `\Exception` class.

### Usage

Throw this exception when encountering errors from vendor code or when wrapping third-party exceptions.

```php
use Haku\Exceptions\VendorException;

try
{
	// Third-party library operation
	$result = $externalLibrary->doSomething();
}
catch (\Exception $e)
{
	throw new VendorException(
		sprintf('External library error: %s', $e->getMessage()),
		0,
		$e
	);
}
```

### When to Use

Use `VendorException` for:
- Third-party library failures
- External service errors
- Vendor code integration issues
- Wrapping non-Haku exceptions

> [!TIP]
> Wrapping third-party exceptions in `VendorException` helps maintain clear error boundaries between framework code and external dependencies.

---

## Exception Hierarchy

Both exception classes extend PHP's native `\Exception` class:

```
\Exception (PHP native)
├── Haku\Exceptions\FrameworkException
└── Haku\Exceptions\VendorException
```

This simple hierarchy makes it easy to catch framework-specific errors separately from vendor errors:

```php
use Haku\Exceptions\{FrameworkException, VendorException};

try
{
	// Application code
}
catch (FrameworkException $e)
{
	// Handle framework errors
	logError('Framework error: ' . $e->getMessage());
}
catch (VendorException $e)
{
	// Handle vendor errors
	logError('Vendor error: ' . $e->getMessage());
}
catch (\Exception $e)
{
	// Handle all other errors
	logError('Unexpected error: ' . $e->getMessage());
}
```

---

## Best Practices

**Be Specific**
Create domain-specific exception classes that extend these base exceptions:

```php
namespace App\Exceptions;

use Haku\Exceptions\FrameworkException;

class DatabaseConnectionException extends FrameworkException {}
class InvalidRouteException extends FrameworkException {}
```

**Include Context**
Always provide descriptive error messages with context:

```php
throw new FrameworkException(
	sprintf(
		'Failed to initialize %s: %s',
		$componentName,
		$reason
	)
);
```

**Chain Exceptions**
Preserve the original exception when wrapping errors:

```php
catch (\Exception $original)
{
	throw new VendorException(
		'External service failed',
		0,
		$original  // Previous exception
	);
}
```

---

## See also

- [[Haku\Errors]] — Error handling and logging system
- [[Haku\Http\Exceptions]] — HTTP-specific exceptions
- [[Haku\Jwt\Exceptions]] — JWT-specific exceptions
