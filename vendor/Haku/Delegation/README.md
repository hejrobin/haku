# Haku\Delegation

Routing and request delegation system for Haku. This package provides attribute-based routing, middleware support, and automatic route discovery from the `app/routes` directory.

---

## Overview

The Delegation package includes:
- **Attribute-Based Routing** — Define routes using PHP 8 attributes (`#[Route]`)
- **Automatic Route Discovery** — Scans `app/routes/` directory
- **Path Parameters** — Dynamic URL segments with type hints
- **Middleware Support** — Per-route and controller-level middleware
- **Method Spoofing** — Support for PUT/PATCH/DELETE via `_METHOD`
- **Request Delegation** — Matches URL paths to route handlers

---

## Defining Routes

### Basic Route

```php
namespace App\Routes;

use Haku\Delegation\Route;
use Haku\Http\{Method, Messages\Json, Status};

class Users
{
    #[Route('/users', Method::Get)]
    public function index(): Json
    {
        $users = User::findAll();

        return Json::from(['users' => $users]);
    }
}
```

### Route with Parameters

```php
#[Route('/users/{id}', Method::Get)]
public function show(int $id): Json
{
    $user = User::find($id);

    if (!$user) {
        return Json::error('User not found', Status::NotFound);
    }

    return Json::from(['user' => $user->json()]);
}
```

### Multiple HTTP Methods

```php
#[Route('/users', Method::Post)]
public function create(): Json
{
    // Handle POST /users
}

#[Route('/users/{id}', Method::Put)]
public function update(int $id): Json
{
    // Handle PUT /users/123
}

#[Route('/users/{id}', Method::Delete)]
public function destroy(int $id): Json
{
    // Handle DELETE /users/123
}
```

---

## Path Parameters

### Simple Parameters

Parameters are defined in curly braces and automatically passed to handler methods:

```php
#[Route('/posts/{id}', Method::Get)]
public function show(int $id): Json
{
    // $id is extracted from URL and passed as argument
}

#[Route('/users/{userId}/posts/{postId}', Method::Get)]
public function userPost(int $userId, int $postId): Json
{
    // Multiple parameters in order
}
```

### Type Hints

**Automatic Type Detection:**
- Parameters ending in `id` are treated as `(\d+)` — numbers only
- Use `{name:number}` to explicitly require numbers
- Default is `([\w\-_%]+)` — alphanumeric with dashes, underscores, percent

```php
// Auto-detected as number (ends with 'id')
#[Route('/users/{userId}', Method::Get)]
public function show(int $userId): Json { }

// Explicit number type
#[Route('/posts/{id:number}', Method::Get)]
public function post(int $id): Json { }

// Alphanumeric slug
#[Route('/posts/{slug}', Method::Get)]
public function bySlug(string $slug): Json { }
```

### Optional Parameters

Use `?` after parameter to make it optional:

```php
#[Route('/search/{query}?', Method::Get)]
public function search(?string $query = null): Json
{
    if ($query) {
        return Json::from(searchFor($query));
    }

    return Json::from(getAll());
}
```

---

## Controller-Level Routes

Apply `#[Route]` to a class to set a base path for all methods:

```php
namespace App\Routes;

use Haku\Delegation\Route;
use Haku\Http\{Method, Messages\Json};

#[Route('/api/v1')]
class Api
{
    #[Route('/users', Method::Get)]  // Maps to /api/v1/users
    public function users(): Json { }

    #[Route('/posts', Method::Get)]  // Maps to /api/v1/posts
    public function posts(): Json { }
}
```

---

## Haku\Delegation\Middleware

Abstract base class for creating middlewares.

### Creating Middleware

```php
namespace App\Middlewares;

use Haku\Delegation\Middleware;
use Haku\Http\{Request, Message, Headers, Status, Messages\Json};

class AuthMiddleware extends Middleware
{
    public function invoke(
        Request $request,
        Message $response,
        Headers $headers
    ): array
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (!$token) {
            $response = Json::error('Unauthorized', Status::Unauthorized);
        }

        return [$request, $response, $headers];
    }
}
```

### Applying Middleware

**Per-Route:**

```php
use Haku\Delegation\{Route, Uses};
use Haku\Http\Method;

#[Route('/profile', Method::Get)]
#[Uses(['auth'])]  // Applies App\Middlewares\Auth
public function profile(): Json { }
```

**Per-Controller:**

```php
#[Route('/api')]
#[Uses(['auth', 'rate_limit'])]
class ProtectedApi
{
    // All methods inherit these middlewares

    #[Route('/profile', Method::Get)]
    public function profile(): Json { }

    #[Route('/settings', Method::Get)]
    #[Uses(['admin'])]  // Additional middleware for this route
    public function settings(): Json { }
}
```

**Middleware Resolution:**
- `'auth'` resolves to `App\Middlewares\Auth`
- `'auth/bearer'` resolves to `App\Middlewares\Auth\Bearer`
- `'@cors'` resolves to `Haku\Delegation\Middlewares\Cors` (framework middleware)

---

## Additional Attributes

### Haku\Delegation\WithStatus

Set HTTP status code for a route:

```php
use Haku\Delegation\{Route, WithStatus};
use Haku\Http\Method;

#[Route('/maintenance', Method::Get)]
#[WithStatus(503)]
public function maintenance(): Json
{
    return Json::from(['message' => 'Under maintenance']);
}
```

### Haku\Delegation\WithHeaders

Add custom headers to response:

```php
use Haku\Delegation\{Route, WithHeaders};
use Haku\Http\Method;

#[Route('/download/{file}', Method::Get)]
#[WithHeaders([
    'Content-Type' => 'application/octet-stream',
    'Content-Disposition' => 'attachment'
])]
public function download(string $file): Json { }
```

---

## Route Naming

### Automatic Names

Route names are auto-generated from controller and method names:

```php
namespace App\Routes;

class Users
{
    #[Route('/users', Method::Get)]
    public function index(): Json { }
    // Auto-named: "users_index"

    #[Route('/users/{id}', Method::Get)]
    public function show(int $id): Json { }
    // Auto-named: "users_show"
}
```

### Explicit Names

```php
#[Route('/users', Method::Get, name: 'user.list')]
public function index(): Json { }

#[Route('/users/{id}', Method::Get, name: 'user.detail')]
public function show(int $id): Json { }
```

---

## Request Delegation

The `delegate()` function processes incoming requests:

```php
use function Haku\Delegation\delegate;
use Haku\Http\Headers;

// In index.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$headers = new Headers();

try {
    [$request, $response, $headers] = delegate($path, $headers);

    $headers->send();
    echo $response;
} catch (StatusException $e) {
    // Handle HTTP errors
}
```

**Process:**
1. Loads all routes from `app/routes/`
2. Resolves HTTP method (including `_METHOD` spoofing)
3. Matches path against route patterns
4. Extracts path parameters
5. Processes through middlewares
6. Invokes route handler
7. Returns `[Request, Message, Headers]`

---

## Complete Examples

### RESTful API Controller

```php
namespace App\Routes\Api;

use Haku\Delegation\{Route, Uses};
use Haku\Http\{Method, Messages\Json, Status, Exceptions\StatusException};
use App\Models\Post;

#[Route('/api/posts')]
#[Uses(['auth'])]
class Posts
{
    #[Route('/', Method::Get)]
    public function index(): Json
    {
        $posts = Post::paginate(page: 1, limit: 25);

        return Json::from($posts);
    }

    #[Route('/{id}', Method::Get)]
    public function show(int $id): Json
    {
        $post = Post::find($id);

        if (!$post) {
            throw new StatusException(404);
        }

        return Json::from(['post' => $post->json()]);
    }

    #[Route('/', Method::Post)]
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
            Status::Created
        );
    }

    #[Route('/{id}', Method::Put)]
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

    #[Route('/{id}', Method::Delete)]
    public function destroy(int $id): Json
    {
        $post = Post::find($id);

        if (!$post) {
            throw new StatusException(404);
        }

        Post::delete($id);

        return Json::from(
            ['message' => 'Post deleted'],
            Status::OK
        );
    }
}
```

### Nested Routes

```php
namespace App\Routes;

#[Route('/users/{userId}')]
class UserResources
{
    #[Route('/posts', Method::Get)]
    public function posts(int $userId): Json
    {
        // GET /users/123/posts
        $posts = Post::findAll([
            Where::is('user_id', $userId)
        ]);

        return Json::from(['posts' => $posts]);
    }

    #[Route('/comments', Method::Get)]
    public function comments(int $userId): Json
    {
        // GET /users/123/comments
    }
}
```

### Middleware Chain

```php
namespace App\Middlewares;

use Haku\Delegation\Middleware;
use Haku\Http\{Request, Message, Headers};

class LogRequest extends Middleware
{
    public function invoke(
        Request $request,
        Message $response,
        Headers $headers
    ): array
    {
        // Log before handler
        error_log("Request: {$_SERVER['REQUEST_URI']}");

        return [$request, $response, $headers];
    }
}

class RateLimit extends Middleware
{
    public function invoke(
        Request $request,
        Message $response,
        Headers $headers
    ): array
    {
        // Check rate limit
        if (exceedsRateLimit()) {
            $response = Json::error('Too many requests', Status::TooManyRequests);
        }

        return [$request, $response, $headers];
    }
}

// Apply to route
#[Route('/api/search', Method::Get)]
#[Uses(['log_request', 'rate_limit'])]
public function search(): Json { }
```

---

## Viewing Routes

List all registered routes:

```bash
# Show all routes
php haku routes

# Generate Postman collection
php haku routes --postman

# Inspect full route definitions
php haku routes --inspect
```

---

## Best Practices

**Organize by Resource**
```
app/routes/
├── Users.php
├── Posts.php
├── Comments.php
└── Api/
    ├── Auth.php
    └── Admin.php
```

**Use Nested Controllers for Related Resources**
```php
#[Route('/api')]
class Api
{
    #[Route('/users', Method::Get)]
    public function users(): Json { }

    #[Route('/posts', Method::Get)]
    public function posts(): Json { }
}
```

**Apply Middleware at Controller Level**
```php
#[Route('/admin')]
#[Uses(['auth', 'admin'])]
class Admin
{
    // All methods require auth + admin middleware
}
```

**Use Type Hints for Parameters**
```php
// ✓ Good - explicit types
#[Route('/posts/{id:number}', Method::Get)]
public function show(int $id): Json { }

// ✗ Avoid - ambiguous
#[Route('/posts/{id}', Method::Get)]
public function show($id): Json { }
```

**Return Consistent Response Types**
```php
// All methods return Json
public function index(): Json { }
public function show(int $id): Json { }
public function create(): Json { }
```

---

## See also

- [[Haku\Http]] — HTTP messages, status codes, and methods
- [[Haku\Console]] — Route listing command (`php haku routes`)
- [[Haku\Spec]] — Route testing with `route()` helper
- [[Haku\Jwt]] — Authentication middleware
