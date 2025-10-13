# Testing Directory

This directory contains test fixtures, mock models, and other testing resources.

## Structure

```
testing/
├── models/          # Test models using Testing\Models namespace
└── README.md        # This file
```

## Usage

### Test Models

Test models should use the `Testing\Models` namespace and follow the same structure as application models:

```php
<?php
declare(strict_types=1);

namespace Testing\Models;

use Haku\Database\Model;
use Haku\Database\Attributes\{Entity, PrimaryKey};

#[Entity('test_table_name')]
class TestModel extends Model
{
    #[PrimaryKey]
    protected readonly int $id;

    // ... other properties
}
```

### Using Test Models

The SchemaParser and other framework components automatically check both `app/models` and `testing/models` directories, so you can reference test models by name without namespace qualifiers:

```php
$parser = new SchemaParser();
$parser->parse('TestUser'); // Finds Testing\Models\TestUser
```

## Notes

- Test models are kept separate from application code
- All test models should be prefixed with "Test" for clarity
- Use test models only in `*.spec.php` test files
- Do not use test models in production application code
