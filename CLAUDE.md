# Haku - Project Guide for Claude

## Project Overview
Haku is a lightweight, opinionated web application framework for building JSON APIs in PHP 8.3+. It follows a "batteries included" philosophy with zero external dependencies, relying solely on native PHP features.

**Current Version:** 0.2.0
**Status:** Development (not production-ready)
**License:** MIT

## Core Philosophy
- Native PHP 8.3 features only - no Composer dependencies
- Small footprint and minimal external requirements
- Built-in testing framework inspired by Jest
- Follows PSR-1 coding standards
- JSON API-focused with CORS support

## Project Structure

```
haku/
├── index.php              # Main entry point, handles requests & exceptions
├── bootstrap.php          # Bootstrap configuration
├── haku                   # CLI tool for development
├── config.dev.php         # Development environment config
├── config.test.php        # Test environment config
├── app/                   # Application code
│   ├── components/        # Reusable components
│   ├── middlewares/       # Request/response middlewares
│   ├── migrations/        # Database migrations
│   ├── models/            # Data models
│   ├── payloads/          # Request/response payloads
│   ├── queries/           # Database queries
│   └── routes/            # Route definitions
├── vendor/Haku/           # Framework core (not Composer)
└── private/               # Private files (databases, logs, etc.)
```

## Key Components

### Entry Point ([index.php](index.php))
- Delegates requests through `Haku\Delegation\delegate()`
- Handles CORS headers automatically
- Catches `StatusException` and generic `Throwable` errors
- Returns JSON responses with appropriate HTTP status codes
- Includes stack traces in dev/test environments

### Request Flow
1. Bootstrap loads environment and configuration
2. Headers initialized with CORS settings
3. Request delegated based on URL path
4. Response buffered and returned as JSON
5. Exceptions caught and formatted as JSON errors

### CLI Tool (`php haku`)
- `init` - Creates config files (--dev, --test flags)
- `serve` - Starts development server
- `make <generator>` - Code generation
- `test` - Runs *.spec.php tests (--only, --omit flags)
- `version` - Shows version
- `routes` - Lists all routes
- `upgrade` - Updates from main branch

## Environment Configuration
- `HAKU_ENV` - Environment (dev/test/production)
- `HAKU_CORS_*` - CORS configuration constants
- `HAKU_ROOT_PATH` - Project root directory
- `HAKU_PHP_VERSION` - Required PHP version (8.3.0)

## Database
- Uses PDO PHP Extension
- Migrations stored in [app/migrations/](app/migrations/)
- Queries organized in [app/queries/](app/queries/)
- Models in [app/models/](app/models/)

## Testing
- Built-in test runner (Jest-inspired)
- Test files use `*.spec.php` naming convention
- Run with `php haku test`
- Can filter tests with --only or --omit flags

## Development Workflow
1. Use `php haku serve` for local development
2. Routes defined in [app/routes/](app/routes/)
3. Middlewares process requests in [app/middlewares/](app/middlewares/)
4. Components provide reusable logic
5. Write tests alongside features

## Important Notes for Claude
- **No Composer:** Don't suggest Composer packages - use native PHP only
- **PHP 8.3+ only:** Leverage modern PHP features (enums, attributes, readonly, etc.)
- **JSON APIs:** All responses should be JSON formatted
- **Testing:** Encourage writing specs for new features
- **PSR-1:** Follow PSR-1 coding standards
- **Namespaces:** All code uses `Haku\` namespace prefix
- **Type Safety:** Use strict types (`declare(strict_types=1)`)
- **Error Handling:** Throw `StatusException` for HTTP errors

## Common Tasks

### Adding a New Route
1. Create route file in [app/routes/](app/routes/)
2. Use code generator: `php haku make route`
3. Verify with: `php haku routes`

### Adding a Model
1. Create model in [app/models/](app/models/)
2. Use code generator: `php haku make model`
3. Add corresponding migration if needed

### Writing Tests
1. Create `*.spec.php` file next to code
2. Follow Jest-like syntax provided by framework
3. Run with `php haku test`

## Server Configuration
- Sample configs provided for Apache (.htaccess) and Nginx
- All requests should route through [index.php](index.php)
- Requires mbstring and PDO extensions

## External Resources
- PHP 8.3: https://www.php.net/releases/8.3/en.php
- GitHub: https://github.com/hejrobin/haku
- PSR-1: https://www.php-fig.org/psr/psr-1/
