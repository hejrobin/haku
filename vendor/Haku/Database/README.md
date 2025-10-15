# Haku\Database

Complete database abstraction layer for Haku. This package provides an ORM-like experience with models, query builders, relations, migrations, and PDO-based connections.

---

## Overview

The Database package includes:
- **Active Record Pattern** — Models with built-in CRUD operations
- **Query Builders** — Type-safe query construction (Find, Where, Write)
- **Relations** — BelongsTo, HasOne, HasMany relationships
- **Soft Deletes** — Non-destructive record deletion
- **Migrations** — Database schema versioning
- **Attributes** — PHP 8 attributes for model configuration
- **Validation** — Built-in model validation
- **Marshalling** — Data transformation for JSON serialization

> [!IMPORTANT]
> Configure database connection constants in your config file before using the Database package.

---

## Configuration

### Required Constants

```php
// config.php
define('HAKU_DATABASE_TYPE', 'mysql');
define('HAKU_DATABASE_HOST', 'localhost');
define('HAKU_DATABASE_PORT', 3306);
define('HAKU_DATABASE_NAME', 'your_database');
define('HAKU_DATABASE_USER', 'your_username');
define('HAKU_DATABASE_PASS', 'your_password');
```

### Supported Database Types

```php
use Haku\Database\ConnectionType;

ConnectionType::MySQL    // MySQL/MariaDB
ConnectionType::SQLite   // SQLite
```

---

## Haku\Database\Connection

PDO-based database connection class with query execution helpers.

### Creating Connections

```php
use Haku\Database\{Connection, ConnectionType};

$db = new Connection(
    type: ConnectionType::MySQL,
    database: 'myapp',
    host: 'localhost',
    port: 3306
);

$db->login('username', 'password');
```

### Query Execution Methods

**`fetch(string $query, array $params): ?array`**
Fetches a single row as an associative array.

```php
$user = $db->fetch(
    'SELECT * FROM users WHERE id = :id',
    ['id' => 123]
);
```

**`fetchAll(string $query, array $params): ?array`**
Fetches all rows as an array of associative arrays.

```php
$users = $db->fetchAll(
    'SELECT * FROM users WHERE active = :active',
    ['active' => 1]
);
```

**`fetchColumn(string $query, array $params): mixed`**
Fetches a single column value.

```php
$count = $db->fetchColumn(
    'SELECT COUNT(*) FROM users',
    []
);
```

**`execute(string $query, array $params): bool`**
Executes a query (INSERT, UPDATE, DELETE) and returns success status.

```php
$success = $db->execute(
    'UPDATE users SET name = :name WHERE id = :id',
    ['name' => 'John', 'id' => 123]
);
```

### Connection Methods

```php
// Get last inserted ID
$id = $db->lastInsertId();

// Begin transaction
$db->beginTransaction();

// Commit transaction
$db->commit();

// Rollback transaction
$db->rollBack();

// Check if in transaction
if ($db->inTransaction()) {
    // ...
}
```

---

## Haku\Database\Model

Abstract base class for creating database models with Active Record pattern.

### Defining Models

```php
namespace App\Models;

use Haku\Database\Model;
use Haku\Database\Attributes\{Entity, PrimaryKey, Timestamp, Validates};

#[Entity('users')]
class User extends Model
{
    #[PrimaryKey]
    protected readonly int $id;

    #[Validates(['required', 'email'])]
    protected string $email;

    #[Validates(['required', 'min:3'])]
    protected string $name;

    protected ?string $bio;

    #[Timestamp]
    protected readonly int $createdAt;

    #[Timestamp]
    protected readonly ?int $updatedAt;
}
```

### Finding Records

**Find by Primary Key**

```php
$user = User::find(123);

if ($user) {
    echo $user->name;
}
```

**Find One by Conditions**

```php
use Haku\Database\Query\Where;

$user = User::findOne([
    Where::is('email', 'john@example.com')
]);
```

**Find All**

```php
use Haku\Database\Query\Where;

$users = User::findAll(
    where: [
        Where::is('active', 1)
    ],
    orderBy: ['name' => 'ASC'],
    limit: 50,
    offset: 0
);
```

**Pagination**

```php
$result = User::paginate(
    where: [Where::is('active', 1)],
    orderBy: ['created_at' => 'DESC'],
    page: 1,
    limit: 25
);

// Returns:
// [
//     'pagination' => [...],
//     'meta' => [...],
//     'records' => [...]
// ]
```

### Creating and Updating Records

**Creating New Records**

```php
$user = new User();
$user->hydrate([
    'email' => 'jane@example.com',
    'name' => 'Jane Doe',
    'bio' => 'Software developer'
]);

// Validate
$errors = $user->validate();

if (count($errors) === 0) {
    $savedUser = $user->save();
}
```

**Updating Existing Records**

```php
$user = User::find(123);

if ($user) {
    $user->hydrate(['name' => 'John Updated']);
    $errors = $user->validate();

    if (count($errors) === 0) {
        $user->save();
    }
}
```

### Deleting Records

**Soft Delete (if configured)**

```php
// Marks record as deleted without removing it
User::delete(123);
```

**Force Delete**

```php
// Permanently removes the record
User::delete(123, forceDelete: true);
```

**Restoring Soft-Deleted Records**

```php
$user = User::find(123, includeDeleted: true);

if ($user) {
    $restoredUser = $user->restore();
}
```

### JSON Serialization

```php
$user = User::find(123);

// Serialize to array
$data = $user->json();

// With additional fields from joins
$data = $user->json(additionalFields: ['role_name']);

// Skip validation
$data = $user->json(skipValidation: true);
```

### Marshalling and Mutating

**Marshallers** transform data for output (JSON serialization):

```php
class User extends Model
{
    protected string $distance;

    // Convert meters to kilometers for JSON output
    protected function marshalDistance(float $value): float
    {
        return $value / 1000;
    }
}
```

**Mutators** transform data before saving to database:

```php
class User extends Model
{
    protected string $password;

    // Hash password before saving
    protected function mutatePassword(string $value): string
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }
}
```

### Relations

Define relationships using the `#[Relation]` attribute:

```php
use Haku\Database\Attributes\Relation;
use Haku\Database\RelationType;

class User extends Model
{
    #[Relation(model: Role::class, type: RelationType::BelongsTo, foreignKey: 'role_id')]
    protected ?Role $role;

    #[Relation(model: Post::class, type: RelationType::HasMany, foreignKey: 'user_id')]
    protected array $posts;
}

// Load relations
$user = User::find(123);
$user->loadRelation('role');
$user->loadRelation('posts', limit: 10);

// Or load all relations
$user->loadAllRelations();
```

**Relation Types:**
- `RelationType::BelongsTo` — This model has foreign key to related model
- `RelationType::HasOne` — Related model has foreign key to this model (1:1)
- `RelationType::HasMany` — Related model has foreign key to this model (1:many)

---

## Haku\Database\Query\Where

Type-safe WHERE clause builder for queries.

### Comparison Methods

```php
use Haku\Database\Query\Where;

// Equality
Where::is('status', 'active')           // status = 'active'
Where::not('status', 'deleted')         // status != 'deleted'

// Comparison
Where::greaterThan('age', 18)           // age > 18
Where::greaterThanOrEqual('age', 18)    // age >= 18
Where::lessThan('price', 100)           // price < 100
Where::lessThanOrEqual('price', 100)    // price <= 100

// NULL checks
Where::null('deleted_at')               // deleted_at IS NULL
Where::notNull('verified_at')           // verified_at IS NOT NULL

// IN clause
Where::in('status', ['active', 'pending'])  // status IN (...)
Where::notIn('id', [1, 2, 3])               // status NOT IN (...)

// LIKE patterns
Where::like('name', '%john%')           // name LIKE '%john%'
Where::notLike('email', '%spam%')       // name NOT LIKE '%spam%'

// BETWEEN
Where::between('created_at', $start, $end)  // created_at BETWEEN ... AND ...
```

### Usage in Queries

```php
$users = User::findAll([
    Where::is('active', 1),
    Where::greaterThan('age', 18),
    Where::notNull('verified_at'),
    Where::like('name', '%john%')
]);
```

---

## Haku\Database\Migration\Migration

Interface for defining database migrations.

### Creating Migrations

```php
namespace App\Migrations;

use Haku\Database\Migration\Migration;
use Haku\Database\Connection;

class CreateUsersTable implements Migration
{
    public function up(Connection $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `email` VARCHAR(255) UNIQUE NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(Connection $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `users`;");
    }

    // Optional: Seed data
    public function seed(Connection $db): void
    {
        $db->exec("
            INSERT INTO `users` (`email`, `name`)
            VALUES
                ('admin@example.com', 'Admin'),
                ('user@example.com', 'Test User');
        ");
    }
}
```

### Running Migrations

```bash
# Run all pending migrations
php haku migrate

# Run migrations and seed data
php haku migrate --seed

# Revert last migration
php haku migrate --down
```

### Generating Migrations from Models

```bash
# Generate migration from model attributes
php haku make migration create_user_table --from User
```

This reads the model's attributes and generates appropriate SQL:

```php
#[Entity('users')]
class User extends Model
{
    #[PrimaryKey]
    protected readonly int $id;

    protected string $name;  // VARCHAR(255) NOT NULL

    #[Timestamp]
    protected readonly int $createdAt;
}

// Generates CREATE TABLE with all columns properly typed
```

---

## Attributes

### Haku\Database\Attributes\Entity

Specifies the database table name for a model.

```php
#[Entity('users')]
class User extends Model {}
```

### Haku\Database\Attributes\PrimaryKey

Marks a property as the primary key.

```php
#[PrimaryKey]
protected readonly int $id;
```

### Haku\Database\Attributes\Timestamp

Marks a property as a timestamp field with automatic SQL generation.

```php
// Auto-populates on INSERT
#[Timestamp]
protected readonly string $createdAt;

// Auto-updates on UPDATE
#[Timestamp]
protected readonly string $updatedAt;
```

### Haku\Database\Attributes\Schema

Override column definition with custom SQL.

```php
#[Schema('VARCHAR(100) UNIQUE NOT NULL')]
protected string $email;

#[Schema('TEXT')]
protected string $bio;
```

### Haku\Database\Attributes\Validates

Define validation rules for a property.

```php
#[Validates(['required', 'email'])]
protected string $email;

#[Validates(['required', 'min:8'])]
protected string $password;

#[Validates(['numeric', 'min:0', 'max:150'])]
protected ?int $age;
```

### Haku\Database\Attributes\Relation

Define model relationships.

```php
#[Relation(model: Role::class, type: RelationType::BelongsTo, foreignKey: 'role_id')]
protected ?Role $role;
```

### Haku\Database\Attributes\Omitted

Exclude property from database operations.

```php
#[Omitted]
protected string $temporaryValue;
```

### Haku\Database\Attributes\Aggregate

Mark property as an aggregate field (from JOIN or computed column).

```php
#[Aggregate]
protected ?int $post_count;
```

---

## Helper Functions

### `Haku\Database\isConfigured()`

Checks if database configuration constants are defined.

```php
use function Haku\Database\isConfigured;

if (isConfigured()) {
    // Initialize database connection
}
```

### `Haku\Database\databaseType()`

Checks if the configured database matches a specific type.

```php
use function Haku\Database\databaseType;
use Haku\Database\ConnectionType;

if (databaseType(ConnectionType::MySQL)) {
    // MySQL-specific code
}
```

### `Haku\Database\sqlValueFrom()`

Converts PHP values to SQL-safe strings.

```php
use function Haku\Database\sqlValueFrom;

$value = sqlValueFrom(true);      // 'TRUE'
$value = sqlValueFrom(123);       // 123
$value = sqlValueFrom('text');    // '"text"'
$value = sqlValueFrom(null);      // null
```

---

## Usage Examples

### Complete CRUD Example

```php
use App\Models\User;
use Haku\Database\Query\Where;

// CREATE
$user = new User();
$user->hydrate([
    'email' => 'john@example.com',
    'name' => 'John Doe'
]);

$errors = $user->validate();

if (count($errors) === 0) {
    $savedUser = $user->save();
    echo "Created user with ID: {$savedUser->id}";
}

// READ
$user = User::find(123);
if ($user) {
    echo "Found: {$user->name}";
}

// UPDATE
$user->hydrate(['name' => 'John Updated']);
if (count($user->validate()) === 0) {
    $user->save();
}

// DELETE
User::delete(123);
```

### Working with Relations

```php
class Post extends Model
{
    #[Relation(model: User::class, type: RelationType::BelongsTo, foreignKey: 'user_id')]
    protected ?User $author;

    #[Relation(model: Comment::class, type: RelationType::HasMany, foreignKey: 'post_id')]
    protected array $comments;
}

// Load and access relations
$post = Post::find(1);
$post->loadRelation('author');
$post->loadRelation('comments', limit: 10);

echo $post->author->name;
foreach ($post->comments as $comment) {
    echo $comment->content;
}
```

### Transaction Example

```php
use function Haku\haku;

$db = haku('db');

try {
    $db->beginTransaction();

    $user = new User();
    $user->hydrate(['email' => 'test@example.com', 'name' => 'Test']);
    $user->save();

    $profile = new Profile();
    $profile->hydrate(['user_id' => $user->id, 'bio' => 'Test bio']);
    $profile->save();

    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
    throw $e;
}
```

---

## Best Practices

**Use Validation**
Always validate before saving:

```php
$errors = $user->validate();
if (count($errors) > 0) {
    // Handle validation errors
    return ['errors' => $errors];
}
```

**Use Transactions for Multiple Operations**
Wrap related operations in transactions:

```php
$db->beginTransaction();
try {
    // Multiple operations
    $db->commit();
} catch (\Exception $e) {
    $db->rollBack();
}
```

**Use Prepared Statements**
The Connection class automatically uses prepared statements with parameter binding.

**Leverage Soft Deletes**
For audit trails and data recovery, use soft deletes:

```php
// Soft delete (preserves data)
User::delete(123);

// Restore later
$user->restore();
```

---

## See also

- [[Haku\Schema]] — Schema attributes and validation rules
- [[Haku\Console]] — CLI commands for migrations (`php haku migrate`)
- [[Haku\Spec]] — Testing framework for model tests
- [[Haku\Errors]] — Error handling for database exceptions
