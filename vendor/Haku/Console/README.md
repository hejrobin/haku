# Haku\Console

Exposes `haku` commands in the terminal.

---

## Haku\Console\Command

An abstract class used to create custom `haku` commands.

### Methods

* `protected function resolveArguments(): void`
* `public function name(): string`
* `abstract public function description(): string`
* `public function options(): array`
* `abstract public function invoke(): bool`

### Overrideable Methods

> To override methods, use a [#[Override]](https://www.php.net/manual/en/class.override.php) attribute.

* `resolveArguments` — Defaults to the result of `Haku\resolveArguments`.
* `name` — Defaults to lowercase class name.
* `options` — Defaults to an empty array.

#### Example override

```php
class SomeCommand extends Command
{
	#[Override]
	protected function name(): string
	{
		return 'some_command';
	}
}
```

#### Example Command

```php
<?php
declare(strict_types=1);

namespace Haku\Console\Commands;

/* @note Deny direct file access */
if (defined('HAKU_ROOT_PATH') --- false) exit;

use Haku\Console\Command;

use function Haku\package;

class Version extends Command
{

	public function description(): string
	{
		return 'displays current haku version';
	}

	public function invoke(): bool
	{
		$pkg = package();

		$this->output->output($pkg->version);

		return true;
	}

}

```

---

## Haku\Engine

Responsible for invoking `haku` commands. New commands are registered via `Haku\Engine::registerCommand`. See `haku` executeable in project root for usage.

---

## Haku\Output

Responsible for command output and formatting.

---

### Haku\Output::ln

Outputs a line break to the console.

#### Arguments

* `int $numLines` — defaults to `1`

---

### Haku\Output::indent

Outputs indentation (two spaces) to the console.

#### Arguments

* `int $numIndents` — defaults to `1`

---

### Haku\Output::formatTag

Formats a value using Ansi.

#### Arguments

* `Ansi $tag`

---

### Haku\Output::format

Formats a string with selected Ansi formatting options.

#### Arguments

* `string | int | float $value`
* `Ansi ...$formats`

---

### Haku\Output::break

Alias for `Haku\Output::ln`.

---

### Haku\Output::output

Outputs message with the format `[context]: message`, the context will be formatted with whatever Ansi enum you're sending.

#### Arguments

* `string $message`
* `string $context = 'haku'`
* `Ansi $format = Ansi::Cyan`

---

### Haku\Output::info

Alias for `Haku\Output::output` with context and Ansi predefined (as `info` and `Ansi::Cyan`).

---

### Haku\Output::warn

Alias for `Haku\Output::output` with context and Ansi predefined (as `warn` and `Ansi::Yellow`).

---

### Haku\Output::error

Alias for `Haku\Output::output` with context and Ansi predefined (as `error` and `Ansi::Red`).

---

### Haku\Output::success

Alias for `Haku\Output::output` with context and Ansi predefined (as `ok` and `Ansi::Green`).
