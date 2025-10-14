# Haku\Jwt

JSON Web Token (JWT) implementation for Haku. This package provides a complete JWT solution with encoding, decoding, validation, and authorization helpers using HMAC-based algorithms.

---

## Overview

The JWT package implements RFC 7519 JSON Web Tokens with:
- **HS256, HS384, HS512 algorithms** — HMAC-SHA signing
- **Token expiration and validation** — Time-based claims (iat, exp, nbf)
- **Custom payload support** — Store arbitrary data in tokens
- **Authorization helpers** — Bearer token extraction and verification
- **Integrity checking** — Signature validation to prevent tampering

> [!IMPORTANT]
> Define `HAKU_JWT_SIGNING_KEY` in your configuration before using JWT functions. This secret key is used to sign and verify tokens.

---

## Haku\Jwt\Token

Main class for creating, encoding, and decoding JWTs.

### Creating Tokens

```php
use Haku\Jwt\{Token, Algorithm};

$token = new Token(Algorithm::HS256);

// Set standard claims
$token->issuedAt(time());
$token->expiresAt(time() + 3600);  // Expires in 1 hour
$token->claimableAt(time());        // Can be used immediately

// Add custom data
$token->set('user_id', 123);
$token->set('role', 'admin');

// Encode to JWT string
$jwt = $token->encode(Algorithm::HS256, 'your-secret-key');
```

### Decoding Tokens

```php
use Haku\Jwt\Token;

$jwt = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';

$token = Token::decode(
    token: $jwt,
    algorithm: Algorithm::HS256,
    signingKey: 'your-secret-key'
);

// Access payload
$userId = $token->get('user_id');
$role = $token->get('role');
```

### Methods

**Time-based Claims**

```php
// Set/get issued at timestamp
$token->issuedAt(time());
$issuedAt = $token->issuedAt();

// Set/get expiration timestamp
$token->expiresAt(time() + 3600);
$expiresAt = $token->expiresAt();

// Set/get "not before" timestamp
$token->claimableAt(time() + 300);  // Valid after 5 minutes
$claimableAt = $token->claimableAt();

// Check validity
$expired = $token->hasExpired();      // bool
$claimable = $token->isClaimable();   // bool
```

**Payload Management**

```php
// Set custom claim
$token->set('key', 'value');

// Get claim
$value = $token->get('key');        // mixed|null

// Check if claim exists
$exists = $token->has('key');       // bool

// Remove claim
$token->remove('key');

// Get entire payload
$payload = $token->getPayload();    // array

// Set entire payload
$token->setPayload(['key' => 'value']);
```

**Reserved Claims**

The following JWT standard claims are protected and cannot be modified directly:
- `iss` — Issuer
- `sub` — Subject
- `aud` — Audience
- `exp` — Expiration time
- `nbf` — Not before
- `iat` — Issued at
- `jti` — JWT ID
- `typ` — Token type

> [!WARNING]
> Attempting to use `set()` or `remove()` on reserved keys will throw a `TokenException`.

---

## Haku\Jwt\Algorithm

Enum-like class defining supported JWT algorithms.

### Available Algorithms

```php
use Haku\Jwt\Algorithm;

Algorithm::HS256  // HMAC with SHA-256
Algorithm::HS384  // HMAC with SHA-384
Algorithm::HS512  // HMAC with SHA-512
```

### Methods

```php
// Get algorithm configuration
$alg = Algorithm::get(Algorithm::HS256);
echo $alg->crypt;     // 'SHA256'
echo $alg->protocol;  // 'hash_hmac'

// Check if algorithm is supported
$supported = Algorithm::isAvailable('HS256');  // true

// Get all available algorithms
$algorithms = Algorithm::getAvailableAlgorithms();
// ['HS256', 'HS384', 'HS512', 'HMAC']
```

---

## Haku\Jwt\Authorization

Static helper class for creating and verifying authorization tokens.

### Creating Authorization Tokens

```php
use Haku\Jwt\Authorization;

$jwt = Authorization::make(
    identifier: 123,      // User ID or similar
    scope: 'admin'        // User role/scope
);

// Returns encoded JWT string
```

### Verifying Identity

```php
use Haku\Jwt\Authorization;

// Check if current request token belongs to specific user
$authorized = Authorization::verifyIdentifier(123);  // bool
```

### Verifying Scope

```php
use Haku\Jwt\Authorization;

// Check if current request token has required scope
$hasScope = Authorization::verifyScope(['admin', 'moderator']);  // bool
```

> [!NOTE]
> Authorization methods work with the current request's Bearer token, extracted automatically from the `Authorization` header.

---

## Helper Functions

### `Haku\Jwt\encodeToken()`

Convenience function for encoding tokens with context data.

```php
use function Haku\Jwt\encodeToken;

$jwt = encodeToken(
    context: [
        'user_id' => 123,
        'role' => 'admin'
    ],
    maxAge: 3600  // Optional, defaults to HAKU_JWT_TOKEN_TTL
);
```

**Parameters:**
- `$context` (array) — Data to store in token
- `$maxAge` (int) — Expiration time in seconds (default: `HAKU_JWT_TOKEN_TTL` or 60)

**Returns:** Encoded JWT string

**Throws:** `TokenException` if `HAKU_JWT_SIGNING_KEY` is not defined

### `Haku\Jwt\decodeToken()`

Convenience function for decoding JWT strings.

```php
use function Haku\Jwt\decodeToken;

$token = decodeToken($jwtString);

$context = $token->get('ctx');
```

**Parameters:**
- `$authToken` (string) — JWT string to decode

**Returns:** `Token` instance

**Throws:** `IntegrityException` if signature validation fails

### `Haku\Jwt\getAuthorizationBearerToken()`

Extracts Bearer token from Authorization header.

```php
use function Haku\Jwt\getAuthorizationBearerToken;

$jwt = getAuthorizationBearerToken();  // string|null

// From header: "Authorization: Bearer eyJhbGc..."
// Returns: "eyJhbGc..."
```

**Returns:** JWT string or `null` if no Authorization header found

### `Haku\Jwt\currentToken()`

Gets the decoded token from the current request.

```php
use function Haku\Jwt\currentToken;

$token = currentToken();  // Token|null

if ($token)
{
    $userId = $token->get('user_id');
}
```

**Returns:** Decoded `Token` instance or `null` if no valid token found

### `Haku\Jwt\validateTokenTimestamp()`

Validates a timestamp is within acceptable range.

```php
use function Haku\Jwt\validateTokenTimestamp;

$valid = validateTokenTimestamp(time());  // bool
```

---

## Usage Examples

### API Authentication

```php
use function Haku\Jwt\{encodeToken, currentToken};
use Haku\Http\Exceptions\StatusException;

// Login endpoint - create token
function login(string $username, string $password): array
{
    $user = authenticateUser($username, $password);

    if (!$user)
    {
        throw new StatusException('Invalid credentials', 401);
    }

    $token = encodeToken([
        'user_id' => $user->id,
        'username' => $user->username,
        'role' => $user->role
    ], maxAge: 86400);  // 24 hours

    return [
        'token' => $token,
        'expires_in' => 86400
    ];
}

// Protected endpoint - verify token
function getProfile(): array
{
    $token = currentToken();

    if (!$token || $token->hasExpired())
    {
        throw new StatusException('Unauthorized', 401);
    }

    $userId = $token->get('user_id');

    return getUserProfile($userId);
}
```

### Role-Based Authorization

```php
use function Haku\Jwt\currentToken;
use Haku\Http\Exceptions\StatusException;

function requireRole(string ...$allowedRoles): void
{
    $token = currentToken();

    if (!$token)
    {
        throw new StatusException('Unauthorized', 401);
    }

    if ($token->hasExpired())
    {
        throw new StatusException('Token expired', 401);
    }

    $userRole = $token->get('role');

    if (!in_array($userRole, $allowedRoles))
    {
        throw new StatusException('Forbidden', 403);
    }
}

// Use in routes
function deleteUser(int $id): void
{
    requireRole('admin', 'moderator');

    // Admin-only logic
    performDelete($id);
}
```

### Custom Claims

```php
use Haku\Jwt\{Token, Algorithm};

$token = new Token(Algorithm::HS256);

$token->issuedAt(time());
$token->expiresAt(time() + 3600);

// Add custom claims
$token->set('permissions', ['read', 'write', 'delete']);
$token->set('tenant_id', 'company-123');
$token->set('session_id', 'sess_abc123');

$jwt = $token->encode(Algorithm::HS256, HAKU_JWT_SIGNING_KEY);

// Later, decode and use
$decoded = Token::decode($jwt, Algorithm::HS256, HAKU_JWT_SIGNING_KEY);

$permissions = $decoded->get('permissions');
$tenantId = $decoded->get('tenant_id');
```

### Token Refresh

```php
use function Haku\Jwt\{currentToken, encodeToken};
use Haku\Http\Exceptions\StatusException;

function refreshToken(): array
{
    $oldToken = currentToken();

    if (!$oldToken)
    {
        throw new StatusException('No token provided', 401);
    }

    if ($oldToken->hasExpired())
    {
        throw new StatusException('Token expired, please login again', 401);
    }

    // Create new token with same payload
    $payload = $oldToken->getPayload();
    unset($payload['iat'], $payload['exp'], $payload['nbf']);

    $newToken = encodeToken($payload, maxAge: 86400);

    return [
        'token' => $newToken,
        'expires_in' => 86400
    ];
}
```

---

## Configuration

### Required Constants

```php
// config.php
define('HAKU_JWT_SIGNING_KEY', 'your-secret-key-here');  // Required
define('HAKU_JWT_TOKEN_TTL', 3600);                      // Optional, default: 60
```

> [!CAUTION]
> Never commit your `HAKU_JWT_SIGNING_KEY` to version control. Use environment-specific configuration files and keep them out of your repository.

### Generating Signing Keys

```php
// Generate a secure random key
use function Haku\Generic\Strings\random;

$signingKey = random(64);  // 64-byte random string
```

Or use PHP's native functions:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

---

## Exception Handling

### Exception Types

**`Haku\Jwt\Exceptions\TokenException`**
General token errors (invalid format, missing required fields)

**`Haku\Jwt\Exceptions\IntegrityException`**
Signature validation failures, tampered tokens

**`Haku\Jwt\Exceptions\AlgorithmException`**
Unsupported or invalid algorithms

### Example

```php
use Haku\Jwt\Exceptions\{TokenException, IntegrityException};
use function Haku\Jwt\decodeToken;

try
{
    $token = decodeToken($jwtString);
}
catch (IntegrityException $e)
{
    // Token was tampered with
    logSecurityEvent('Invalid token signature', $jwtString);
    throw new StatusException('Invalid token', 401);
}
catch (TokenException $e)
{
    // Invalid token format or expired
    throw new StatusException('Invalid or expired token', 401);
}
```

---

## Security Best Practices

**Use Strong Signing Keys**
- Minimum 32 bytes (256 bits)
- Use cryptographically secure random generation
- Rotate keys periodically

**Set Appropriate Expiration**
```php
// Short-lived tokens are more secure
$token->expiresAt(time() + 900);  // 15 minutes
```

**Validate on Every Request**
```php
$token = currentToken();

if (!$token || $token->hasExpired() || !$token->isClaimable())
{
    throw new StatusException('Unauthorized', 401);
}
```

**Don't Store Sensitive Data**
```php
// ✗ Bad - tokens can be decoded by anyone
$token->set('password', $user->password);
$token->set('credit_card', $cardNumber);

// ✓ Good - store identifiers only
$token->set('user_id', $user->id);
```

**Use HTTPS Only**
Always transmit JWTs over HTTPS to prevent interception.

---

## See also

- [[Haku\Http\Messages]] — Request and response handling for JWT middleware
- [[Haku\Errors]] — Error handling for JWT exceptions
- [[Haku\Generic\Strings]] — String utilities including random key generation
