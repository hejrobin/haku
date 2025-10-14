# Haku\Core

Core design patterns and utilities for Haku. This package provides reusable traits for implementing common design patterns including Singleton and Factory patterns.

---

## Haku\Core\Singleton

Trait that implements the Singleton pattern, ensuring only one instance of a class exists throughout the application lifecycle.

### Usage

```php
namespace App\Services;

use Haku\Core\Singleton;

class Cache
{
    use Singleton;

    private array $data = [];

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
}

// Get the singleton instance
$cache = Cache::getInstance();
$cache->set('key', 'value');

// Same instance everywhere
$sameCache = Cache::getInstance();
echo $sameCache->get('key');  // 'value'
```

### Methods

**`getInstance(): self`**

Returns the singleton instance, creating it if it doesn't exist.

```php
$instance = MyClass::getInstance();
```

- **Static method** — Call on the class, not an object
- **Lazy initialization** — Instance created on first call
- **Thread-safe** — Only one instance ever created

### Enforced Restrictions

The Singleton trait prevents multiple instantiation by:

- **Private constructor** — Cannot use `new MyClass()`
- **Final `__clone()`** — Cannot clone the instance
- **Final `__wakeup()`** — Cannot unserialize the instance

```php
// ✗ All of these will fail:
$instance = new Cache();           // Error: constructor is private
$copy = clone $instance;           // Error: __clone() is final
$unserialized = unserialize($s);  // Error: __wakeup() is final

// ✓ Only this works:
$instance = Cache::getInstance();
```

### When to Use

Use the Singleton trait for:
- **Global state managers** — Caches, configuration, registries
- **Resource managers** — Database connections, file handles
- **Service locators** — Dependency containers
- **Logging services** — Single log writer

> [!WARNING]
> Singletons create global state and can make testing difficult. Consider dependency injection as an alternative for better testability.

### Example: Configuration Manager

```php
namespace App\Core;

use Haku\Core\Singleton;

class Config
{
    use Singleton;

    private array $config = [];

    public function load(string $path): void
    {
        $this->config = require $path;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}

// Anywhere in your application
$config = Config::getInstance();
$config->load('config.php');

// Later, in another file
$config = Config::getInstance();
echo $config->get('app.name');  // Same instance, data persists
```

---

## Haku\Core\Factory

Trait that implements the Factory pattern for managing named object instances, useful for service containers and dependency injection.

### Usage

```php
namespace App\Core;

use Haku\Core\{Singleton, Factory};

class Container
{
    use Singleton;
    use Factory;
}

// Initialize and register services
$container = Container::getInstance();

$container->initialize(
    className: 'App\Services\Logger',
    instanceName: 'logger'
);

$container->initialize(
    className: 'App\Services\Mailer',
    instanceName: 'mailer',
    classMethodName: 'create',
    classMethodArguments: ['smtp.example.com', 587]
);

// Retrieve services
$logger = $container->get('logger');
$mailer = $container->get('mailer');
```

### Methods

**`has(string $instanceName): bool`**

Checks if an instance with the given name exists in the factory.

```php
if ($container->has('database'))
{
    $db = $container->get('database');
}
```

**`get(string $instanceName): ?object`**

Retrieves a registered instance by name. Returns `null` if not found.

```php
$logger = $container->get('logger');

if ($logger === null)
{
    // Handle missing service
}
```

**`set(string $instanceName, object $instance): void`**

Registers an instance with a name. Protected method, typically used internally.

```php
protected function registerLogger(): void
{
    $logger = new Logger();
    $this->set('logger', $logger);
}
```

**Throws:** `Haku\Exceptions\FrameworkException` if instance name already exists.

**`initialize(string $className, ?string $instanceName, ?string $classMethodName, array $classMethodArguments): object`**

Creates and registers a new instance using reflection.

```php
$db = $container->initialize(
    className: 'App\Database\Connection',
    instanceName: 'db',
    classMethodName: 'connect',
    classMethodArguments: ['localhost', 'mydb', 'user', 'pass']
);
```

**Parameters:**
- `$className` — Fully qualified class name
- `$instanceName` — Name to register under (default: lowercase class basename)
- `$classMethodName` — Static method to call (default: 'newInstance')
- `$classMethodArguments` — Arguments to pass to the method

**Returns:** The created instance

**Throws:** `Haku\Exceptions\FrameworkException` if instance name already exists.

### Default Instance Naming

If `$instanceName` is not provided, the factory generates one from the class name:

```php
// Class: App\Services\EmailService
$container->initialize('App\Services\EmailService');

// Registered as: 'emailService'
$service = $container->get('emailService');
```

### When to Use

Use the Factory trait for:
- **Service containers** — Managing application services
- **Dependency injection** — Registering and resolving dependencies
- **Plugin managers** — Loading and caching plugins
- **Resource pools** — Managing reusable resources

### Example: Service Container

```php
namespace App\Core;

use Haku\Core\{Singleton, Factory};

class Services
{
    use Singleton;
    use Factory;

    public function boot(): void
    {
        // Register core services
        $this->initialize('App\Services\Logger', 'logger');
        $this->initialize('App\Services\Cache', 'cache');
        $this->initialize('App\Services\Router', 'router');
    }
}

// Bootstrap
$services = Services::getInstance();
$services->boot();

// Use anywhere
function getService(string $name): ?object
{
    return Services::getInstance()->get($name);
}

$logger = getService('logger');
$logger->info('Application started');
```

### Example: Database Pool

```php
namespace App\Database;

use Haku\Core\Factory;

class ConnectionPool
{
    use Factory;

    public function addConnection(
        string $name,
        string $host,
        string $database
    ): void
    {
        $connection = $this->initialize(
            className: 'App\Database\Connection',
            instanceName: $name,
            classMethodName: 'newInstance',
            classMethodArguments: [$host, $database]
        );
    }
}

$pool = new ConnectionPool();
$pool->addConnection('primary', 'localhost', 'main_db');
$pool->addConnection('replica', 'replica.host', 'main_db');

$primary = $pool->get('primary');
$replica = $pool->get('replica');
```

---

## Combining Singleton and Factory

A common pattern is to combine both traits for a global service container:

```php
namespace App\Core;

use Haku\Core\{Singleton, Factory};
use Haku\Exceptions\FrameworkException;

class App
{
    use Singleton;
    use Factory;

    public function register(string $name, callable $factory): void
    {
        if ($this->has($name))
        {
            throw new FrameworkException(
                "Service '{$name}' is already registered"
            );
        }

        $instance = $factory();
        $this->set($name, $instance);
    }

    public function service(string $name): object
    {
        $service = $this->get($name);

        if ($service === null)
        {
            throw new FrameworkException(
                "Service '{$name}' not found"
            );
        }

        return $service;
    }
}

// Register services
$app = App::getInstance();

$app->register('config', fn() => new Config());
$app->register('logger', fn() => new Logger());

// Use services
$config = $app->service('config');
$logger = $app->service('logger');
```

---

## Best Practices

**Use Dependency Injection Over Singletons**

Prefer passing dependencies explicitly rather than using singletons:

```php
// ✗ Avoid: Hidden dependency
class UserService
{
    public function create(array $data)
    {
        $logger = Logger::getInstance();  // Hidden dependency
        $logger->info('Creating user');
    }
}

// ✓ Better: Explicit dependency
class UserService
{
    public function __construct(
        private Logger $logger
    ) {}

    public function create(array $data)
    {
        $this->logger->info('Creating user');
    }
}
```

**Keep Factory Instances Private**

Don't expose the internal `$__instances` array:

```php
// ✗ Bad
public function getAllInstances(): array
{
    return $this->__instances;
}

// ✓ Good
public function has(string $name): bool
{
    return $this->has($name);
}
```

**Use Type Hints**

Create typed getters for better IDE support:

```php
class Services
{
    use Factory;

    public function logger(): Logger
    {
        return $this->get('logger');
    }

    public function cache(): Cache
    {
        return $this->get('cache');
    }
}

// Now with autocomplete!
$logger = $services->logger();
```

---

## See also

- [[Haku\Exceptions]] — FrameworkException used by Factory
- [[Haku\Database]] — Uses Factory pattern for connection management
- [[Haku\Spec]] — Uses Singleton pattern for test runner
