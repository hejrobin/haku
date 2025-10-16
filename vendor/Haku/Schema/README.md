# Haku\Schema

Data validation framework for Haku. This package provides declarative validation rules for model attributes with detailed error reporting.

---

## Overview

The Schema package includes:
- **Declarative Validation** — Define rules via `#[Validates]` attribute
- **Rich Validation Rules** — Required, length, type, format, regex, and more
- **Optional Fields** — Support for optional field validation
- **Range Validation** — Min/max length and numeric ranges
- **Format Validators** — Email, timestamps, strong passwords
- **Custom Error Messages** — Detailed validation failure messages

---

## Basic Usage

### Model Validation

```php
use Haku\Database\Model;
use Haku\Database\Attributes\{Entity, Validates};

#[Entity('users')]
class User extends Model
{
	#[Validates(['required', 'email'])]
	protected string $email;

	#[Validates(['required', 'len:3..50'])]
	protected string $name;

	#[Validates(['optional', 'len:..500'])]
	protected ?string $bio;

	#[Validates(['required', 'strongPassword'])]
	protected string $password;
}

// Validate model
$user = new User();
$user->hydrate($_POST);

$errors = $user->validate();

if (count($errors) > 0) {
	// Handle validation errors
	return Json::from(
		['errors' => $errors],
		Status::BadRequest
	);
}
```

---

## Validation Rules

### Required and Optional

**`required`** — Field must be present in data

```php
#[Validates(['required'])]
protected string $email;
```

**`optional`** — Skip validation if field is empty

```php
#[Validates(['optional', 'email'])]
protected ?string $email;
```

**`omitted`** — Field must NOT be present

```php
#[Validates(['omitted'])]
protected string $internalField;
```

**`requiredWith`** — Field required if another field exists

```php
#[Validates(['requiredWith:password'])]
protected string $passwordConfirmation;
```

### Length Validation

**`len:N`** — Exact length

```php
#[Validates(['len:10'])]
protected string $phoneNumber;  // Must be exactly 10 chars
```

**`len:N..`** — Minimum length

```php
#[Validates(['len:8..'])]
protected string $password;  // At least 8 chars
```

**`len:..N`** — Maximum length

```php
#[Validates(['len:..100'])]
protected string $username;  // Max 100 chars
```

**`len:N..M`** — Range

```php
#[Validates(['len:3..50'])]
protected string $name;  // Between 3 and 50 chars
```

### Type Validation

**`bool`** — Must be boolean or boolean string

```php
#[Validates(['bool'])]
protected bool $isActive;  // Accepts: true, false, '1', '0', 'true', 'false'
```

### Equality

**`eq:field`** — Must match another field

```php
#[Validates(['eq:password'])]
protected string $passwordConfirmation;
```

### Enumerations

**`enum:val1,val2,val3`** — Must be one of specified values

```php
#[Validates(['enum:draft,published,archived'])]
protected string $status;
```

### Format Validation

**`emailAddress`** — Valid RFC 822 email

```php
#[Validates(['required', 'emailAddress'])]
protected string $email;
```

**`strongPassword`** — Strong password requirements
- At least 8 characters
- Contains lowercase and uppercase
- Contains at least one digit or special character

```php
#[Validates(['required', 'strongPassword'])]
protected string $password;
```

**`unixTimestamp`** — Valid UNIX timestamp

```php
#[Validates(['unixTimestamp'])]
protected int $createdAt;
```

**`text`** — Within MySQL TEXT column limit (65,535 bytes)

```php
#[Validates(['text'])]
protected string $content;
```

### Pattern Matching

**`regex:pattern`** — Match custom regex (without delimiters)

```php
#[Validates(['regex:^[A-Z]{3}\d{3}$'])]
protected string $productCode;  // Like "ABC123"
```

---

## Combining Rules

Combine multiple rules by adding them to the array:

```php
#[Validates(['required', 'email', 'len:..255'])]
protected string $email;

#[Validates(['optional', 'len:10..1000', 'text'])]
protected ?string $description;

#[Validates(['required', 'enum:active,inactive,pending'])]
protected string $status;
```

---

## Validation Examples

### User Registration

```php
#[Entity('users')]
class User extends Model
{
	#[Validates(['required', 'emailAddress', 'len:..255'])]
	protected string $email;

	#[Validates(['required', 'len:3..50'])]
	protected string $username;

	#[Validates(['required', 'strongPassword', 'len:8..'])]
	protected string $password;

	#[Validates(['optional', 'len:..500'])]
	protected ?string $bio;

	#[Validates(['optional', 'regex:^https?://'])]
	protected ?string $website;
}

// Usage
$user = new User();
$user->hydrate([
	'email' => 'john@example.com',
	'username' => 'john_doe',
	'password' => 'SecurePass123',
	'bio' => 'Developer',
	'website' => 'https://example.com'
]);

$errors = $user->validate();

if (count($errors) > 0) {
	foreach ($errors as $field => $fieldErrors) {
		echo "{$field}: " . implode(', ', $fieldErrors) . "\n";
	}
}
```

### Product Model

```php
#[Entity('products')]
class Product extends Model
{
	#[Validates(['required', 'len:3..100'])]
	protected string $name;

	#[Validates(['required', 'len:10..'])]
	protected string $description;

	#[Validates(['required', 'regex:^\d+\.\d{2}$'])]  // Decimal with 2 places
	protected string $price;

	#[Validates(['required', 'enum:draft,published,discontinued'])]
	protected string $status;

	#[Validates(['required', 'regex:^[A-Z]{3}-\d{6}$'])]  // Like "PRD-123456"
	protected string $sku;

	#[Validates(['optional', 'len:..1000'])]
	protected ?string $specifications;
}
```

### Settings Model

```php
#[Entity('settings')]
class Setting extends Model
{
	#[Validates(['required', 'len:3..50'])]
	protected string $key;

	#[Validates(['required'])]
	protected string $value;

	#[Validates(['bool'])]
	protected bool $isPublic;

	#[Validates(['optional', 'enum:string,number,boolean,json'])]
	protected ?string $type;
}
```

---

## Error Handling

### Validation Error Format

Validation errors are returned as an array with field names as keys:

```php
$errors = $user->validate();

// Structure:
// [
//	 'email' => ['\'email\': is required, but is not present in record'],
//	 'name' => ['\'name\': invalid length, expected between 3 and 50, got: 1'],
//	 'password' => ['\'password\': is not a strong password']
// ]
```

### Checking for Errors

```php
$errors = $user->validate();

if (count($errors) > 0) {
	// Has validation errors
	$hasEmailError = isset($errors['email']);

	// Get specific field errors
	$emailErrors = $errors['email'] ?? [];
}
```

### Returning Validation Errors

```php
use Haku\Http\{Messages\Json, Status};

$user = new User();
$user->hydrate($_POST);

$errors = $user->validate();

if (count($errors) > 0) {
	return Json::from(
		['errors' => $errors],
		Status::BadRequest
	);
}
```

---

## Advanced Usage

### Custom Validation in Models

Add custom validation logic in your model:

```php
class User extends Model
{
	#[Validates(['required', 'emailAddress'])]
	protected string $email;

	public function validate(): array
	{
		// Run standard validation
		$errors = parent::validate();

		// Add custom validation
		if ($this->age < 13) {
			$errors['age'] = ['Must be at least 13 years old'];
		}

		// Check uniqueness
		$existing = User::findOne([Where::is('email', $this->email)]);
		if ($existing && $existing->id !== $this->id) {
			$errors['email'] = ['Email already in use'];
		}

		return $errors;
	}
}
```

### Conditional Validation

```php
class Order extends Model
{
	#[Validates(['required', 'enum:card,paypal,bank'])]
	protected string $paymentMethod;

	#[Validates(['optional', 'len:16'])]
	protected ?string $cardNumber;

	public function validate(): array
	{
		$errors = parent::validate();

		// Require card number if payment method is card
		if ($this->paymentMethod === 'card' && empty($this->cardNumber)) {
			$errors['cardNumber'] = ['Card number required for card payments'];
		}

		return $errors;
	}
}
```

---

## Validation Rule Reference

| Rule | Description | Example |
|------|-------------|---------|
| `required` | Field must be present | `['required']` |
| `optional` | Skip validation if empty | `['optional', 'email']` |
| `omitted` | Field must not be present | `['omitted']` |
| `requiredWith:field` | Required if other field exists | `['requiredWith:password']` |
| `eq:field` | Must equal another field | `['eq:password']` |
| `len:N` | Exact length | `['len:10']` |
| `len:N..` | Minimum length | `['len:8..']` |
| `len:..N` | Maximum length | `['len:..100']` |
| `len:N..M` | Length range | `['len:3..50']` |
| `bool` | Boolean value | `['bool']` |
| `enum:a,b,c` | One of specified values | `['enum:draft,published']` |
| `regex:pattern` | Match regex pattern | `['regex:^\d{5}$']` |
| `emailAddress` | Valid email address | `['emailAddress']` |
| `strongPassword` | Strong password | `['strongPassword']` |
| `unixTimestamp` | Valid UNIX timestamp | `['unixTimestamp']` |
| `text` | Within TEXT column limit | `['text']` |

---

## Best Practices

**Validate Before Saving**
```php
$errors = $model->validate();

if (count($errors) > 0) {
	return Json::from(['errors' => $errors], Status::BadRequest);
}

$model->save();
```

**Use Appropriate Rules**
```php
// ✓ Good - specific rules
#[Validates(['required', 'emailAddress', 'len:..255'])]
protected string $email;

// ✗ Avoid - too permissive
#[Validates(['required'])]
protected string $email;
```

**Mark Optional Fields**
```php
// ✓ Good - explicitly optional
#[Validates(['optional', 'len:..500'])]
protected ?string $bio;

// ✗ Avoid - unclear requirements
#[Validates(['len:..500'])]
protected ?string $bio;
```

**Provide User-Friendly Errors**
```php
public function validate(): array
{
	$errors = parent::validate();

	// Transform technical errors into user-friendly messages
	if (isset($errors['password'])) {
		$errors['password'] = [
			'Password must be at least 8 characters with uppercase, lowercase, and numbers'
		];
	}

	return $errors;
}
```

---

## See also

- [[Haku\Database]] — Model validation integration
- [[Haku\Http]] — Error response formatting
- [[Haku\Spec]] — Testing validation logic
