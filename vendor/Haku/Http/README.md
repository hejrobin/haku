# Haku\Http

HTTP abstraction layer for Haku. This package provides type-safe HTTP handling with request/response management, status codes, headers, methods, and message formatting for building JSON APIs.

---

## Overview

The Http package includes:
- **HTTP Status Codes** — Enum-based status code management
- **HTTP Methods** — Type-safe request method handling
- **Headers Management** — Standardized header handling with auto-normalization
- **Message Abstraction** — Base class for response messages
- **JSON/Plain Messages** — Built-in response formatters
- **Request Processing** — Route handling with middleware support
- **Status Exceptions** — HTTP error handling with proper status codes

---

## Haku\Http\Status

Enum providing all standard HTTP status codes with helper methods.

### Available Status Codes

**Success (2xx)**
```php
use Haku\Http\Status;

Status::OK                               // 200
Status::Created                          // 201
Status::Accepted                         // 202
Status::NoContent                        // 204
```

**Redirect (3xx)**
```php
Status::MovedPermanently                 // 301
Status::Found                            // 302
Status::SeeOther                         // 303
Status::NotModified                      // 304
Status::TemporaryRedirect                // 307
```

**Client Error (4xx)**
```php
Status::BadRequest                       // 400
Status::Unauthorized                     // 401
Status::Forbidden                        // 403
Status::NotFound                         // 404
Status::MethodNotAllowed                 // 405
Status::Conflict                         // 409
Status::TooManyRequests                  // 429
```

**Server Error (5xx)**
```php
Status::InternalServerError              // 500
Status::NotImplemented                   // 501
Status::BadGateway                       // 502
Status::ServiceUnavailable               // 503
Status::GatewayTimeout                   // 504
```

> [!NOTE]
> The Status enum also includes humorous "Developer Error" codes (7xx) from the [7XX-rfc](https://github.com/joho/7XX-rfc) like `Status::Meh` (701), `Status::ComputerSaysNo` (740), and `Status::ZombieApocalypse` (793).

### Methods

```php
// Get status code as integer
$code = Status::OK->getCode();  // 200

// Get status type
$type = Status::NotFound->getType();  // 'Client Error'

// Get status type number
$typeNum = Status::OK->getTypeNumber();  // 2

// Get human-readable name
$name = Status::NotFound->getName();  // 'Not Found'

// Format as HTTP status line
$line = Status::OK->asString();  // 'HTTP/1.1 200 OK'

// Resolve current HTTP response code
$status = Status::resolve();  // Status enum from current http_response_code()
```

### Usage Examples

```php
use Haku\Http\{Status, Messages\Json};

// Return success response
return Json::from(['message' => 'Success'], Status::OK);

// Return created resource
return Json::from($user, Status::Created);

// Return error response
return Json::error('Not found', Status::NotFound);
```

---

## Haku\Http\Method

Enum representing HTTP request methods.

### Available Methods

```php
use Haku\Http\Method;

Method::Get
Method::Post
Method::Put
Method::Patch
Method::Delete
Method::Head
Method::Options
Method::Trace
Method::Connect
```

### Methods

**`resolve(): self`**

Resolves the current request method, with support for method spoofing via `_METHOD` field.

```php
$method = Method::resolve();

// Supports _METHOD form field for PUT/PATCH/DELETE
// <input type="hidden" name="_METHOD" value="DELETE">
```

**`asString(): string`**

Returns uppercase string representation.

```php
$method = Method::Post;
echo $method->asString();  // 'POST'
```

**`allowsPayload(): bool`**

Checks if the method typically carries a request body.

```php
Method::Post->allowsPayload();    // true
Method::Put->allowsPayload();     // true
Method::Patch->allowsPayload();   // true
Method::Get->allowsPayload();     // false
Method::Delete->allowsPayload();  // false
```

---

## Haku\Http\Headers

Manages HTTP headers with automatic normalization and validation.

### Creating Headers

```php
use Haku\Http\{Headers, Status};

$headers = new Headers(
    initialHeaders: [
        'Content-Type' => 'application/json',
        'X-Custom' => 'value'
    ],
    status: Status::OK
);
```

### Setting Headers

```php
// Set single header
$headers->set('Content-Type', 'application/json');

// Append multiple headers
$headers->append([
    'Cache-Control' => 'no-cache',
    'X-Rate-Limit' => '1000'
]);

// Headers are automatically normalized
$headers->set('content type', 'text/html');
// Becomes: 'Content-Type: text/html'

// Custom headers get X- prefix
$headers->set('my header', 'value');
// Becomes: 'X-My-Header: value'
```

### Reading Headers

```php
// Check if header exists
if ($headers->has('Content-Type')) {
    // ...
}

// Get header value
$contentType = $headers->get('Content-Type');

// Check header value
if ($headers->is('Content-Type', 'application/json')) {
    // ...
}

// Get all headers
$all = $headers->getAll();  // ['Content-Type' => '...', ...]

// Get flattened headers (for debugging)
$flat = $headers->getAll(flatten: true);
// ['Content-Type: application/json', 'X-Custom: value']
```

### Removing Headers

```php
$headers->remove('X-Custom');

// Remove all headers
$headers->flush();
```

### Status Management

```php
// Set status
$headers->status(Status::Created);

// Get status
$status = $headers->getStatus();
```

### Sending Headers

```php
// Send all headers to client
$headers->send();
```

> [!WARNING]
> The `send()` method checks if headers have already been sent and returns early if they have, preventing "headers already sent" errors.

---

## Haku\Http\Message

Abstract base class for response messages (JSON, Plain text, etc.).

### Creating Custom Messages

```php
use Haku\Http\{Message, Status};

class Xml extends Message
{
    protected function render(array $data): string
    {
        // Convert data to XML
        return $this->arrayToXml($data);
    }

    public static function from(
        mixed $data,
        Status $status = Status::OK,
        array $headers = []
    ): self {
        return new self((array) $data, $status, $headers);
    }

    public function valid(): bool
    {
        // Validate XML
        return true;
    }
}
```

### Built-in Methods

```php
// Set data field with sanitization
$message->set('name', $value, 'string');
$message->set('age', $value, 'int');
$message->set('email', $value, 'email');
$message->set('url', $value, 'url');

// Get data field
$name = $message->get('name');

// Remove data field
$message->remove('name');

// Get status
$status = $message->getStatus();

// Get headers
$headers = $message->getHeaders();

// Get message size in bytes
$size = $message->size();

// Render as string
$output = $message->asRendered();
echo $message;  // Uses __toString()
```

---

## Haku\Http\Messages\Json

JSON response message formatter.

### Creating JSON Responses

```php
use Haku\Http\{Messages\Json, Status};

// Simple response
$response = Json::from(['message' => 'Success']);

// With status code
$response = Json::from(
    data: ['user' => $user],
    status: Status::Created
);

// With custom headers
$response = Json::from(
    data: ['items' => $items],
    status: Status::OK,
    headers: ['X-Total-Count' => '100']
);

// Formatting options
$response = Json::from(
    data: $data,
    formatNumbers: true,     // Convert numeric strings to numbers
    prettyPrint: true        // Pretty-print JSON (default in dev)
);
```

### Error Responses

```php
// Quick error response
$response = Json::error('Not found', Status::NotFound);

// Returns: {"error": "Not found"}
```

### Validation

```php
$response = Json::from($data);

if ($response->valid()) {
    // JSON is valid
}
```

### Usage in Routes

```php
namespace App\Routes;

use Haku\Http\{Messages\Json, Status};

class Users
{
    public function index(): Json
    {
        $users = User::findAll(limit: 50);

        return Json::from([
            'users' => $users,
            'total' => count($users)
        ]);
    }

    public function show(int $id): Json
    {
        $user = User::find($id);

        if (!$user) {
            return Json::error('User not found', Status::NotFound);
        }

        return Json::from(['user' => $user->json()]);
    }

    public function create(): Json
    {
        $user = new User();
        $user->hydrate($_POST);

        $errors = $user->validate();

        if (count($errors) > 0) {
            return Json::from(
                ['errors' => $errors],
                Status::BadRequest
            );
        }

        $saved = $user->save();

        return Json::from(
            ['user' => $saved->json()],
            Status::Created
        );
    }
}
```

---

## Haku\Http\Messages\Plain

Plain text response message formatter.

### Creating Plain Text Responses

```php
use Haku\Http\{Messages\Plain, Status};

$response = Plain::from(
    data: 'Hello, World!',
    status: Status::OK,
    headers: ['Content-Type' => 'text/plain']
);
```

---

## Haku\Http\Request

Request processor that handles route execution and middleware.

### Request Flow

```php
use Haku\Http\{Request, Headers, Method};

// Create from route definition
$request = Request::from(
    route: [
        'name' => 'users.show',
        'path' => '/users/{id}',
        'pattern' => '/users/(\d+)',
        'method' => Method::Get,
        'callback' => [Users::class, 'show'],
        'middlewares' => [AuthMiddleware::class],
        'parameters' => [123]
    ],
    headers: new Headers()
);

// Process request through middlewares and handler
[$req, $res, $headers] = $request->process();
```

The `process()` method:
1. Calls the route handler
2. Passes request through each middleware
3. Returns processed request, response, and headers

---

## Haku\Http\Exceptions\StatusException

Exception for HTTP errors with proper status codes.

### Throwing HTTP Errors

```php
use Haku\Http\Exceptions\StatusException;

// Throw 404
throw new StatusException(404);

// Throw 401
throw new StatusException(401);

// Throw 500
throw new StatusException(500);
```

The exception automatically:
- Sets the HTTP status code
- Uses the status name as the exception message
- Extends `FrameworkException` for proper exception hierarchy

### Usage in Routes

```php
use Haku\Http\Exceptions\StatusException;

class Users
{
    public function show(int $id): Json
    {
        $user = User::find($id);

        if (!$user) {
            throw new StatusException(404);
        }

        return Json::from(['user' => $user->json()]);
    }

    public function delete(int $id): Json
    {
        $user = User::find($id);

        if (!$user) {
            throw new StatusException(404);
        }

        // Check permissions
        if (!currentUser()->canDelete($user)) {
            throw new StatusException(403);
        }

        User::delete($id);

        return Json::from(['message' => 'Deleted'], Status::OK);
    }
}
```

### Catching in Error Handlers

```php
// In index.php or error handler
use Haku\Http\Exceptions\StatusException;

try {
    // Application code
} catch (StatusException $e) {
    http_response_code($e->getCode());

    echo json_encode([
        'error' => $e->getMessage(),
        'status' => $e->getCode()
    ]);
}
```

---

## Complete Examples

### API Endpoint with Validation

```php
namespace App\Routes;

use Haku\Http\{Messages\Json, Status, Exceptions\StatusException};
use App\Models\Post;

class Posts
{
    public function create(): Json
    {
        $post = new Post();
        $post->hydrate($_POST);

        $errors = $post->validate();

        if (count($errors) > 0) {
            return Json::from(
                ['errors' => $errors],
                Status::BadRequest
            );
        }

        $saved = $post->save();

        return Json::from(
            ['post' => $saved->json()],
            Status::Created,
            ['Location' => "/posts/{$saved->id}"]
        );
    }

    public function update(int $id): Json
    {
        $post = Post::find($id);

        if (!$post) {
            throw new StatusException(404);
        }

        $post->hydrate($_POST);
        $errors = $post->validate();

        if (count($errors) > 0) {
            return Json::from(
                ['errors' => $errors],
                Status::BadRequest
            );
        }

        $updated = $post->save();

        return Json::from(['post' => $updated->json()]);
    }
}
```

### Custom Response Headers

```php
use Haku\Http\{Messages\Json, Status, Headers};

class Api
{
    public function index(): Json
    {
        $items = Item::paginate(page: 1, limit: 25);

        return Json::from(
            $items,
            Status::OK,
            [
                'X-Total-Count' => $items['meta']['numRecordsTotal'],
                'X-Page-Count' => $items['pagination']['pageCount'],
                'Cache-Control' => 'public, max-age=300'
            ]
        );
    }
}
```

### Error Handling Middleware

```php
namespace App\Middlewares;

use Haku\Http\{Request, Message, Headers, Messages\Json, Status};
use Haku\Http\Exceptions\StatusException;

class ErrorHandler
{
    public function invoke(
        Request $request,
        Message $response,
        Headers $headers
    ): array {
        try {
            return [$request, $response, $headers];
        } catch (StatusException $e) {
            $errorResponse = Json::from(
                [
                    'error' => $e->getMessage(),
                    'status' => $e->getCode()
                ],
                Status::from($e->getCode())
            );

            return [$request, $errorResponse, $headers];
        }
    }
}
```

---

## Best Practices

**Use Type-Safe Status Codes**
```php
// ✓ Good
return Json::from($data, Status::Created);

// ✗ Avoid
http_response_code(201);
return Json::from($data);
```

**Throw StatusException for Errors**
```php
// ✓ Good
if (!$resource) {
    throw new StatusException(404);
}

// ✗ Avoid
if (!$resource) {
    return Json::error('Not found', Status::NotFound);
}
```

**Set Appropriate Headers**
```php
return Json::from(
    $data,
    Status::Created,
    ['Location' => "/resources/{$id}"]
);
```

**Validate Request Methods**
```php
$method = Method::resolve();

if (!$method->allowsPayload() && !empty($_POST)) {
    throw new StatusException(405);
}
```

---

## See also

- [[Haku\Delegation]] — Routing system that uses Http components
- [[Haku\Jwt]] — JWT authentication for API endpoints
- [[Haku\Errors]] — Error handling and logging
- [[Haku\Exceptions]] — Base exception classes
