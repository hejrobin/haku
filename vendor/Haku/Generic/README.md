# Haku\Generic

Generic utility functions for common operations in Haku. This package provides helper functions organized by domain: arrays, strings, passwords, queries, and URLs.

---

## Overview

The Generic package includes utility functions and classes for:
- **Arrays** (`Haku\Generic\Arrays`) — Array filtering and searching utilities
- **Strings** (`Haku\Generic\Strings`) — String case conversion, encoding, and random generation
- **Password** (`Haku\Generic\Password`) — Password hashing and verification
- **Query** (`Haku\Generic\Query`) — Query parameter and filter management classes
- **Url** (`Haku\Generic\Url`) — URL resolution and path utilities

---

## Haku\Generic\Strings

String manipulation, encoding, case conversion, and random generation utilities.

### Functions

```php
use function Haku\Generic\Strings\{
	hyphenate,
	camelCaseFromSnakeCase,
	snakeCaseFromCamelCase,
	encodeBase64Url,
	decodeBase64Url,
	random
};

// Create hyphenated string (URL-friendly)
$slug = hyphenate('Hello World!');  // 'hello-world'
$slug = hyphenate('Hello World!', '_');  // 'hello_world'

// Case conversions
$camelCase = camelCaseFromSnakeCase('user_name');  // 'userName'
$PascalCase = camelCaseFromSnakeCase('user_name', true);  // 'UserName'
$snake_case = snakeCaseFromCamelCase('userName');  // 'user_name'

// URL-safe base64 encoding (used by JWT)
$encoded = encodeBase64Url($data);  // Base64 URL-safe encoded string
$decoded = decodeBase64Url($encoded);  // Original string

// Generate cryptographically secure random string
$token = random(32);  // Base64 encoded 32-byte random string
```

---

## Haku\Generic\Password

Password hashing and verification using PHP's native bcrypt (via `password_hash` and `password_verify`).

### Functions

```php
use function Haku\Generic\Password\{create, verify};

// Hash password with bcrypt (cost: 10)
$hashed = create('my-password');  // string

// Verify password against hash
$valid = verify('my-password', $hashed);  // bool
```

---

## Haku\Generic\Query

Query parameter and filter management classes for working with URL query strings and structured filters.

### Classes

#### Params

Parse and manipulate URL query parameters.

```php
use Haku\Generic\Query\Params;

// Parse query string (or automatically uses $_SERVER['QUERY_STRING'])
$params = new Params('foo=bar&baz=qux');

// Check if parameter exists
$params->has('foo');  // bool

// Get parameter value
$value = $params->get('foo');  // 'bar' or null

// Set parameter (null removes it)
$params->set('page', 2);
$params->set('foo', null);  // Removes 'foo'

// Convert back to query string
$queryString = $params->toString();  // 'baz=qux&page=2'
```

#### Filter

Manage structured filters with operators for complex queries.

```php
use Haku\Generic\Query\{Filter, FilterOperator};

// Create filter from array
$filter = Filter::from([
	['name' => 'age', 'operator' => 'greaterThan', 'values' => [18]],
	['name' => 'status', 'operator' => 'is', 'values' => ['active']]
]);

// Add filter
$filter->add('role', FilterOperator::Is, ['admin']);

// Check if filter exists
$filter->has('age', FilterOperator::GreaterThan);  // bool

// Get specific filter property
$ageFilter = $filter->get('age', FilterOperator::GreaterThan);  // FilterProperty or null

// Remove filter
$filter->remove('status', FilterOperator::Is);

// Get all filters
$allFilters = $filter->getFilters();  // array of FilterProperty

// Convert to JSON
$json = $filter->toString();
```

#### FilterOperator (Enum)

Available filter operators:

```php
use Haku\Generic\Query\FilterOperator;

FilterOperator::Is					  // 'is'
FilterOperator::IsNot				   // 'isNot'
FilterOperator::GreaterThan			 // 'greaterThan'
FilterOperator::NotGreaterThan		  // 'notGreaterThan'
FilterOperator::GreaterThanOrEqualTo	// 'greaterThanOrEqualTo'
FilterOperator::LessThan				// 'lessThan'
FilterOperator::NotLessThan			 // 'notLessThan'
FilterOperator::LessThanOrEqualTo	   // 'lessThanOrEqualTo'
FilterOperator::Like					// 'like'
FilterOperator::NotLike				 // 'notLike'
FilterOperator::Null					// 'null'
FilterOperator::NotNull				 // 'notNull'
FilterOperator::Contains				// 'contains'
FilterOperator::Custom				  // 'custom'
```

#### FilterProperty

Readonly class representing a single filter condition:

```php
use Haku\Generic\Query\{FilterProperty, FilterOperator};

$property = new FilterProperty(
	name: 'age',
	operator: FilterOperator::GreaterThan,
	values: [18]
);

$property->name;	  // 'age'
$property->operator;  // FilterOperator::GreaterThan
$property->values;	// [18]
```

---

## Haku\Generic\Url

URL resolution and path manipulation utilities.

### Functions

```php
use function Haku\Generic\Url\{resolve, path};

// Resolve current request URL
$currentUrl = resolve();  // 'https://example.com:8080/api/users'
$baseUrl = resolve(omitRequestPath: true);  // 'https://example.com:8080'

// Get or normalize URL path
$requestPath = path();  // Gets current request path, e.g., 'api/users'
$normalized = path('Hello World');  // 'hello-world' (hyphenated)
```

---

## Usage Examples

### Generate Secure Token

```php
use function Haku\Generic\Strings\random;

// Generate 32-byte random token
$token = random(32);
```

### Create User with Hashed Password

```php
use function Haku\Generic\Password\{create, verify};

// During registration
$user = new User();
$user->hydrate([
	'email' => $email,
	'password' => create($_POST['password'])
]);
$user->save();

// During login
$user = User::findOne([Where::is('email', $email)]);

if ($user && verify($_POST['password'], $user->password)) {
	// Login successful
}
```

### Working with Query Parameters

```php
use Haku\Generic\Query\Params;

$params = new Params();
$params->set('page', 1);
$params->set('limit', 50);
$params->set('sort', 'name');

$queryString = $params->toString();  // 'page=1&limit=50&sort=name'
$fullUrl = "https://api.example.com/users?{$queryString}";
```

---

## See Also

- [Haku\Jwt](../Jwt/README.md) — Uses String utilities for encoding/decoding
- [Haku\Database](../Database/README.md) — Uses Array utilities for data manipulation
- [Haku\Http](../Http/README.md) — Uses URL and Query utilities
