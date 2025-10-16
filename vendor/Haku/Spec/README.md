# Haku\Spec

Jest-inspired testing framework for Haku. This package provides a complete BDD-style testing solution with expressive syntax, test lifecycle hooks, and route testing utilities.

---

## Overview

The Spec package includes:
- **BDD Syntax** — `describe`, `it`, `expect` for readable tests
- **Test Lifecycle Hooks** — `before`, `after`, `beforeEach`, `afterEach`, `beforeAll`, `afterAll`
- **Rich Expectations** — Fluent assertion API with chainable matchers
- **Route Testing** — Built-in support for testing API routes
- **Tag-Based Filtering** — Run specific test groups with `--tags` and `--exclude-tags`
- **Test Runner** — Singleton runner with detailed reporting
- **File Discovery** — Automatic loading of `*.spec.php` files

---

## Basic Structure

### Test Files

Test files must be named with `.spec.php` extension and use the following structure:

```php
<?php

use function Haku\Spec\{spec, describe, it, expect};

spec('Feature Name', function()
{
	describe('Component', function()
	{
		it('should do something', function()
		{
			expect(true)->toBeTrue();
		});

		it('should do something else', function()
		{
			expect(2 + 2)->toBe(4);
		});
	});
});
```

### Running Tests

```bash
# Run all tests
php haku test

# Run tests matching filter
php haku test --only UserTest

# Exclude specific tests
php haku test --omit database

# Run tests with specific tags
php haku test --tags unit

# Exclude tests with tags
php haku test --exclude-tags slow
```

---

## Test Functions

### `spec()`

Defines a test specification (test file/suite).

```php
use function Haku\Spec\spec;

spec('User Authentication', function()
{
	// Test groups go here
}, tags: ['auth', 'unit']);
```

**Parameters:**
- `$description` — Suite description
- `$container` — Closure containing test groups
- `$tags` — Optional array of tags for filtering

### `describe()`

Defines a test group within a spec.

```php
use function Haku\Spec\describe;

describe('User Model', function()
{
	// Individual tests go here
});
```

### `it()`

Defines an individual test case.

```php
use function Haku\Spec\it;

it('should create a user', function()
{
	$user = new User();
	expect($user)->toBeInstanceOf(User::class);
});
```

---

## Expectations

### Basic Expectations

```php
use function Haku\Spec\expect;

// Equality
expect($value)->toBe(42);		   // Strict equality (===)
expect($value)->toEqual(42);		// Loose equality (==)

// Boolean
expect($value)->toBeTrue();
expect($value)->toBeFalse();

// Null
expect($value)->toBeNull();

// Type checking
expect($user)->toBeInstanceOf(User::class);
expect($value)->toBeTypeOf('string');  // 'string', 'int', 'array', etc.
```

### Negation

Chain `.not()` to negate any expectation:

```php
expect($value)->not()->toBe(0);
expect($result)->not()->toBeNull();
expect($user)->not()->toBeInstanceOf(Admin::class);
```

### Numeric Comparisons

```php
expect($age)->toBeGreaterThan(18);
expect($age)->toBeGreaterThanOrEqualTo(18);
expect($score)->toBeLessThan(100);
expect($score)->toBeLessThanOrEqualTo(100);
expect($value)->toBeWithinRange(10, 20);
```

### Array/Object Expectations

```php
// Array keys and values
expect($array)->toHaveIndexedKey(0);
expect($array)->toHaveIndexedValue('foo');
expect($array)->toInclude('item');

// Object properties
expect($user)->toHaveProperty('name');
expect($user)->toHavePropertyValue('name', 'John');
```

### Callables and Exceptions

```php
// Test if callable returns specific value
expect(fn() => doSomething())->toReturn(true);

// Test if callable throws exception
expect(fn() => throwError())->toThrow(StatusException::class);
```

### Multiple Expectations

Use `expectAll()` to validate multiple expectations at once:

```php
use function Haku\Spec\{expect, expectAll};

$result = expectAll(
	expect($user->name)->toBe('John'),
	expect($user->age)->toBeGreaterThan(18),
	expect($user->email)->toInclude('@')
);

expect($result)->toBeTrue();
```

---

## Test Lifecycle Hooks

### Suite-Level Hooks

**`beforeAll()`** — Runs once before all tests in the spec:

```php
use function Haku\Spec\{spec, beforeAll};

spec('Database Tests', function()
{
	beforeAll(function()
	{
		// Setup database connection
		setupDatabase();
	});

	// Tests...
}, tags: ['database']);
```

**`afterAll()`** — Runs once after all tests in the spec:

```php
use function Haku\Spec\{spec, afterAll};

spec('Database Tests', function()
{
	afterAll(function()
	{
		// Cleanup database
		teardownDatabase();
	});

	// Tests...
});
```

### Group-Level Hooks

**`before()`** — Runs once before all tests in a `describe` block:

```php
use function Haku\Spec\{describe, before};

describe('User CRUD', function()
{
	before(function()
	{
		// Setup test data
		createTestUsers();
	});

	// Tests...
});
```

**`after()`** — Runs once after all tests in a `describe` block:

```php
use function Haku\Spec\{describe, after};

describe('User CRUD', function()
{
	after(function()
	{
		// Cleanup test data
		deleteTestUsers();
	});

	// Tests...
});
```

### Test-Level Hooks

**`beforeEach()`** — Runs before each individual test:

```php
use function Haku\Spec\{describe, beforeEach, it};

describe('User Model', function()
{
	beforeEach(function()
	{
		// Create fresh user for each test
		global $user;
		$user = new User();
	});

	it('should have a name', function()
	{
		global $user;
		expect($user)->toHaveProperty('name');
	});
});
```

**`afterEach()`** — Runs after each individual test:

```php
use function Haku\Spec\{describe, afterEach, it};

describe('File Operations', function()
{
	afterEach(function()
	{
		// Cleanup after each test
		cleanupTestFiles();
	});

	// Tests...
});
```

---

## Route Testing

Test API routes directly with the `route()` helper:

```php
use function Haku\Spec\{spec, describe, it, expect, route};
use Haku\Http\{Method, Status};

spec('API Routes', function()
{
	describe('GET /users', function()
	{
		it('should return users list', function()
		{
			$result = route('/users', Method::Get);

			expect($result->status)->toBe(Status::OK);
			expect($result->response)->toBeInstanceOf(Json::class);
		});
	});

	describe('POST /users', function()
	{
		it('should create a user', function()
		{
			$_POST = [
				'name' => 'John Doe',
				'email' => 'john@example.com'
			];

			$result = route('/users', Method::Post);

			expect($result->status)->toBe(Status::Created);
		});
	});

	describe('GET /users/:id', function()
	{
		it('should return 404 for nonexistent user', function()
		{
			$result = route('/users/99999', Method::Get);

			expect($result->status)->toBe(Status::NotFound);
		});
	});
});
```

**Route Testing Properties:**
- `$result->request` — Request object
- `$result->response` — Response message object
- `$result->headers` — Headers object
- `$result->status` — HTTP status enum

---

## Tagging Tests

Use tags to organize and filter tests:

```php
spec('User Tests', function()
{
	// Tests...
}, tags: ['unit', 'models']);

spec('Database Integration', function()
{
	// Tests...
}, tags: ['integration', 'database']);

spec('Slow E2E Tests', function()
{
	// Tests...
}, tags: ['e2e', 'slow']);
```

**Run by tags:**

```bash
# Run only unit tests
php haku test --tags unit

# Run unit and integration tests
php haku test --tags unit,integration

# Exclude slow tests
php haku test --exclude-tags slow

# Combine filters
php haku test --tags integration --exclude-tags slow
```

> [!NOTE]
> Tests tagged with `database` are automatically skipped if database is not configured.

---

## Complete Examples

### Model Testing

```php
<?php

use function Haku\Spec\{spec, describe, it, expect, beforeEach, afterEach};
use App\Models\User;

spec('User Model', function()
{
	describe('User Creation', function()
	{
		beforeEach(function()
		{
			global $user;
			$user = new User();
		});

		it('should create a valid user', function()
		{
			global $user;

			$user->hydrate([
				'name' => 'John Doe',
				'email' => 'john@example.com'
			]);

			$errors = $user->validate();

			expect(count($errors))->toBe(0);
			expect($user->name)->toBe('John Doe');
		});

		it('should fail validation with invalid email', function()
		{
			global $user;

			$user->hydrate([
				'name' => 'John',
				'email' => 'invalid-email'
			]);

			$errors = $user->validate();

			expect(count($errors))->toBeGreaterThan(0);
		});
	});
}, tags: ['unit', 'models']);
```

### Route Testing with Authentication

```php
<?php

use function Haku\Spec\{spec, describe, it, expect, route};
use function Haku\Jwt\encodeToken;
use Haku\Http\{Method, Status};

spec('Protected API Routes', function()
{
	describe('GET /api/profile', function()
	{
		it('should return 401 without token', function()
		{
			$result = route('/api/profile', Method::Get);

			expect($result->status)->toBe(Status::Unauthorized);
		});

		it('should return user profile with valid token', function()
		{
			$token = encodeToken(['user_id' => 123]);

			$result = route('/api/profile', Method::Get, [
				'Authorization' => "Bearer {$token}"
			]);

			expect($result->status)->toBe(Status::OK);
		});
	});
}, tags: ['integration', 'auth']);
```

### Database Testing

```php
<?php

use function Haku\Spec\{spec, describe, it, expect, beforeAll, afterAll};
use App\Models\Post;

spec('Post Model Database Operations', function()
{
	beforeAll(function()
	{
		// Setup test database
		global $db;
		$db = setupTestDatabase();
	});

	afterAll(function()
	{
		// Cleanup
		global $db;
		teardownTestDatabase($db);
	});

	describe('Post CRUD', function()
	{
		it('should create and retrieve a post', function()
		{
			$post = new Post();
			$post->hydrate([
				'title' => 'Test Post',
				'content' => 'Test content'
			]);

			$saved = $post->save();

			expect($saved)->not()->toBeNull();
			expect($saved->id)->toBeGreaterThan(0);

			$retrieved = Post::find($saved->id);

			expect($retrieved->title)->toBe('Test Post');
		});
	});
}, tags: ['database', 'integration']);
```

---

## Best Practices

**Organize Tests Logically**
```php
spec('Feature Name', function()
{
	describe('Component A', function()
	{
		it('should do X', function() { /* ... */ });
		it('should do Y', function() { /* ... */ });
	});

	describe('Component B', function()
	{
		it('should do Z', function() { /* ... */ });
	});
});
```

**Use Descriptive Names**
```php
// ✓ Good
it('should return 404 when user not found', function() { /* ... */ });

// ✗ Avoid
it('test user', function() { /* ... */ });
```

**Isolate Tests**
Use hooks to ensure each test starts with a clean state:

```php
beforeEach(function()
{
	global $user;
	$user = new User();  // Fresh instance for each test
});
```

**Tag Appropriately**
```php
// Unit tests - fast, no external dependencies
spec('Utils', function() { /* ... */ }, tags: ['unit']);

// Integration tests - database, external services
spec('API Integration', function() { /* ... */ }, tags: ['integration']);

// E2E tests - full application flow
spec('User Journey', function() { /* ... */ }, tags: ['e2e']);
```

**Test Edge Cases**
```php
describe('Division', function()
{
	it('should divide positive numbers', function() { /* ... */ });
	it('should handle division by zero', function() { /* ... */ });
	it('should handle negative numbers', function() { /* ... */ });
});
```

---

## See also

- [[Haku\Console]] — Test runner command (`php haku test`)
- [[Haku\Database]] — Model testing
- [[Haku\Http]] — Route and API testing
- [[Haku\Delegation]] — Route generation for tests
