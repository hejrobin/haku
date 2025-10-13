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
├── haku-init              # Initial setup script (run first)
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

### Initial Setup
**First time setup:** Run `php haku-init` to create initial environment configs
- Creates both `config.dev.php` and `config.test.php` with random signing keys
- Must be run before using any other `php haku` commands

### CLI Tool (`php haku`)
- `env` - Creates/regenerates environment config files (--name dev|test|prod, --regenerate)
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
- Run migrations: `php haku migrate`
- Revert last migration: `php haku migrate --down`
- Run migrations with seeding: `php haku migrate --seed`
- Queries organized in [app/queries/](app/queries/)
- Models in [app/models/](app/models/)

## Testing
- Built-in test runner (Jest-inspired)
- Test files use `*.spec.php` naming convention
- Run with `php haku test`
- Can filter tests with --only or --omit flags

## Development Workflow
1. **First time:** Run `php haku-init` to create environment configs
2. Use `php haku serve` for local development
3. Routes defined in [app/routes/](app/routes/)
4. Middlewares process requests in [app/middlewares/](app/middlewares/)
5. Components provide reusable logic
6. Write tests alongside features

## Important Notes for Claude
- **No Composer:** Don't suggest Composer packages - use native PHP only
- **PHP 8.3+ only:** Leverage modern PHP features (enums, attributes, readonly, etc.)
- **JSON APIs:** All responses should be JSON formatted
- **Testing:** Encourage writing specs for new features
- **PSR-1:** Follow PSR-1 coding standards
- **Namespaces:** All code uses `Haku\` namespace prefix
- **Type Safety:** Use strict types (`declare(strict_types=1)`)
- **Error Handling:** Throw `StatusException` for HTTP errors
- **Code Style:** Use Allman style - opening curly brackets on new lines for functions, classes, conditionals, and loops
- **Package Naming:** Framework packages/namespaces are pluralized (e.g., `Haku\Errors`, `Haku\Http\Messages`, `Haku\Database\Attributes`)

## Common Tasks

### Adding a New Route
1. Create route file in [app/routes/](app/routes/)
2. Use code generator: `php haku make route`
3. Verify with: `php haku routes`

### Adding a Model
1. Create model in [app/models/](app/models/)
2. Use code generator: `php haku make model`
3. Generate migration from model: `php haku make migration ModelName --model`

### Generating Migrations from Models
Models can automatically generate CREATE TABLE migrations by analyzing attributes:

**Supported Attributes:**
- `#[PrimaryKey]` - Generates INT UNSIGNED AUTO_INCREMENT primary key
- `#[Timestamp]` - Generates TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP (auto-updates on row change)
- `#[Timestamp(default: true)]` - Generates TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL (auto-populates on insert)
- `#[Schema('CUSTOM SQL')]` - Override column definition with custom SQL
- `#[Validates()]` - Tracked but doesn't affect schema
- PHP type hints - Automatically mapped to SQL types (int→INT, string→VARCHAR(255), bool→TINYINT(1), etc.)

**Example Model:**
```php
#[Entity('users')]
class User extends Model
{
    #[PrimaryKey]
    protected readonly int $id;

    #[Schema('VARCHAR(100) UNIQUE NOT NULL')]
    protected string $email;

    protected string $name;  // Becomes VARCHAR(255) NOT NULL
    protected ?int $age;      // Becomes INT DEFAULT NULL

    #[Timestamp(default: true)]
    protected readonly string $created_at;

    #[Timestamp]
    protected readonly string $updated_at;
}
```

**Generate Migration:**
```bash
php haku make migration User --model
```

This creates a migration with the complete CREATE TABLE statement based on model attributes.

### Seeding Data in Migrations
Migrations can include an optional `seed()` method to populate tables with initial data:

**Generated migrations include:**
- `up()` - Creates the table
- `down()` - Drops the table
- `seed()` - Optional method for inserting seed data

**Example Migration with Seed:**
```php
class CreateAccountTable implements Migration
{
    public function up(Connection $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS `accounts` (...)");
    }

    public function down(Connection $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `accounts`;");
    }

    public function seed(Connection $db): void
    {
        $db->exec("
            INSERT INTO `accounts` (`email`, `username`, `password`, `is_active`)
            VALUES
                ('admin@example.com', 'admin', 'hashed_pass', 1),
                ('user@example.com', 'testuser', 'hashed_pass', 1);
        ");
    }
}
```

**Running Migrations:**
- `php haku migrate` - Run migrations only
- `php haku migrate --seed` - Run migrations and execute seed() methods
- `php haku migrate --down` - Revert last migration

**Notes:**
- The `seed()` method is optional and only runs when `--seed` flag is used
- Seed failures are caught and logged as warnings (won't stop migration)
- Seeds run after the migration is committed

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
