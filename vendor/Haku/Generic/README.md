# Haku\Generic

Generic utility functions for common operations in Haku. This package provides helper functions organized by domain: arrays, strings, passwords, queries, and URLs.

---

## Overview

The Generic package includes utility functions for:
- **Arrays** (`Haku\Generic\Arrays`) — Array manipulation and filtering
- **Strings** (`Haku\Generic\Strings`) — String operations, encoding, and random generation
- **Passwords** (`Haku\Generic\Password`) — Password hashing and verification
- **Queries** (`Haku\Generic\Query`) — URL query string parsing and building
- **URLs** (`Haku\Generic\Url`) — URL parsing and manipulation

---

## Haku\Generic\Arrays

Array manipulation utilities for filtering, grouping, and transforming data.

### Common Functions

```php
use function Haku\Generic\Arrays\{
    pluck,
    groupBy,
    flatten,
    unique,
    compact
};

// Extract values from array of objects/arrays
$ids = pluck($users, 'id');

// Group array items by key
$byRole = groupBy($users, 'role');

// Flatten nested arrays
$flat = flatten([[1, 2], [3, 4]]);  // [1, 2, 3, 4]

// Remove duplicates
$unique = unique([1, 2, 2, 3]);  // [1, 2, 3]

// Remove null/empty values
$compact = compact([1, null, '', 3]);  // [1, 3]
```

---

## Haku\Generic\Strings

String manipulation, encoding, and generation utilities.

### Common Functions

```php
use function Haku\Generic\Strings\{
    random,
    slug,
    truncate,
    startsWith,
    endsWith,
    contains,
    encodeBase64Url,
    decodeBase64Url
};

// Generate random string
$token = random(32);  // 32-byte random string

// Create URL-friendly slug
$slug = slug('Hello World!');  // 'hello-world'

// Truncate with ellipsis
$short = truncate($longText, 100);  // Max 100 chars + '...'

// String checks
startsWith('hello', 'hel');   // true
endsWith('world', 'ld');      // true
contains('hello world', 'lo'); // true

// URL-safe base64 encoding (used by JWT)
$encoded = encodeBase64Url($data);
$decoded = decodeBase64Url($encoded);
```

---

## Haku\Generic\Password

Password hashing and verification using PHP's native `password_hash` and `password_verify`.

### Functions

```php
use function Haku\Generic\Password\{hash, verify};

// Hash password
$hashed = hash('my-password');

// Verify password
$valid = verify('my-password', $hashed);  // true
```

---

## Haku\Generic\Query

URL query string parsing and building.

### Functions

```php
use function Haku\Generic\Query\{parse, build};

// Parse query string
$params = parse('foo=bar&baz=qux');
// ['foo' => 'bar', 'baz' => 'qux']

// Build query string
$query = build(['foo' => 'bar', 'baz' => 'qux']);
// 'foo=bar&baz=qux'
```

---

## Haku\Generic\Url

URL parsing and manipulation utilities.

### Functions

```php
use function Haku\Generic\Url\{parse, build, isValid};

// Parse URL into components
$parts = parse('https://example.com/path?query=1');
// ['scheme' => 'https', 'host' => 'example.com', ...]

// Build URL from components
$url = build([
    'scheme' => 'https',
    'host' => 'example.com',
    'path' => '/api/users'
]);

// Validate URL
$valid = isValid('https://example.com');  // true
```

---

## Usage Examples

### Generate Secure Token

```php
use function Haku\Generic\Strings\random;

$token = random(64);  // Cryptographically secure random string
```

### Create User with Hashed Password

```php
use function Haku\Generic\Password\{hash, verify};

// During registration
$user = new User();
$user->hydrate([
    'email' => $email,
    'password' => hash($_POST['password'])
]);
$user->save();

// During login
$user = User::findOne([Where::is('email', $email)]);

if ($user && verify($_POST['password'], $user->password)) {
    // Login successful
}
```

### Build API URLs

```php
use function Haku\Generic\{Query\build, Url\parse};

$baseUrl = 'https://api.example.com/users';
$queryString = build([
    'page' => 1,
    'limit' => 50,
    'sort' => 'name'
]);

$fullUrl = "{$baseUrl}?{$queryString}";
```

---

## See also

- [[Haku\Jwt]] — Uses String utilities for encoding/decoding
- [[Haku\Database]] — Uses Array utilities for data manipulation
- [[Haku\Http]] — Uses URL and Query utilities
