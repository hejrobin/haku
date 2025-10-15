# Haku\Console

The Console package provides a complete CLI framework for building terminal commands in Haku. It includes an extensible command system with ANSI formatting support, argument parsing, and a command engine for managing and executing commands.

---

## Available Commands

The `php haku` CLI tool provides the following commands:

| Command | Description | Common Options |
|---------|-------------|----------------|
| `env` | Creates or regenerates environment config files | `--name` (dev/test/prod), `--regenerate` |
| `serve` | Starts the development server | `--port` (8000), `--host` (127.0.0.1), `--env` (dev) |
| `make` | Code generator tools | `generator` (route, model, migration, middleware, spec) |
| `test` | Runs spec tests with optional filters | `--only`, `--omit`, `--tags`, `--exclude-tags` |
| `migrate` | Runs or reverts database migrations | `--down`, `--seed` |
| `routes` | Displays all defined application routes | `--inspect`, `--postman` |
| `version` | Displays current version and checks for updates | `--no-check` |
| `upgrade` | Updates Haku framework from latest release | `--dry-run`, `--backup`, `--force`, `--skip-validation` |

> [!TIP]
> Use `php haku <command> --help` to view detailed help for any command, including all available options and their default values.

---

## Haku\Console\Command

Abstract base class for creating custom Haku terminal commands. All commands must extend this class and implement the required methods.

### Creating a Custom Command

```php
namespace App\Commands;

use Haku\Console\Command;

class MyCommand extends Command
{
    // Required: Return command description
    public function description(): string
    {
        return 'does something useful';
    }

    // Optional: Define command options
    public function options(): array
    {
        return [
            '--force|skip confirmation prompts|',
            '--output|output file path|./output.txt',
        ];
    }

    // Required: Execute the command logic
    public function invoke(): bool
    {
        // Access parsed arguments
        $force = array_key_exists('force', $this->arguments->arguments);
        $outputPath = $this->arguments->arguments['output'] ?? './output.txt';

        // Use output helper
        $this->output->info('processing...');

        // Return true on success, false on failure
        return true;
    }
}
```

### Option Format

Options are defined as pipe-separated strings: `flag|description|default`

```php
public function options(): array
{
    return [
        '--name|the resource name|required',
        '--force|overwrite existing files|',
        '--port|server port|8000',
    ];
}
```

### Available Properties

- `$this->output` — [Haku\Console\Output](#hakuconsoleoutput) instance for terminal output
- `$this->arguments` — Parsed command-line arguments as an object

### Overridable Methods

**`name(): string`**
Returns the command name. Defaults to the class name in lowercase.

```php
public function name(): string
{
    return 'my-custom-name';
}
```

**`resolveArguments(): void`**
Customizes argument parsing. Useful for commands with complex argument structures.

```php
protected function resolveArguments(): void
{
    $this->arguments = (object) resolveArguments(
        triggerNextAsArgument: 'make',
        triggerFieldName: 'generator',
        nextAsArgumentTriggers: ['route', 'model', 'migration']
    );
}
```

---

## Haku\Console\Engine

Manages command registration, execution, and help output. The Engine class handles the entire command lifecycle.

### Basic Usage

```php
use Haku\Console\Engine;
use App\Commands\MyCommand;

$engine = new Engine();
$engine->registerCommand(new MyCommand());
$engine->run();
```

### Features

- **Automatic Help Generation** — Shows available commands when no command is specified
- **Command Not Found Handling** — Displays friendly error messages
- **Per-Command Help** — Use `--help` with any command to view usage details
- **Formatted Output** — Uses [Haku\Console\Ansi](#hakuconsoleansi) formatting for colorized, readable terminal output

### Help System

When a user runs a command with `--help`, the Engine automatically displays:
- Command name and description
- Usage syntax
- All available options with descriptions and defaults

```bash
# Show all commands
php haku

# Show help for specific command
php haku migrate --help
```

---

## Haku\Console\Output

Handles all terminal output with support for ANSI formatting, contextual messages, and structured output.

### Basic Output Methods

```php
use Haku\Console\Output;
use Haku\Console\Ansi;

$output = new Output();

// Send raw output
$output->send('Hello', ' World');  // "Hello World\n"

// Insert line breaks
$output->break(2);  // Insert 2 line breaks

// Formatted output with context
$output->output('server started', 'app', Ansi::Green);
// [app]: server started
```

### Contextual Messages

Pre-formatted message types for common use cases:

```php
$output->info('processing data...');     // [info]: processing data...
$output->success('task completed');      // [ok]: task completed
$output->warn('deprecated feature');     // [warn]: deprecated feature
$output->error('operation failed');      // [error]: operation failed
```

### Formatting

```php
// Format text with ANSI codes
$formatted = $output->format('Important', Ansi::Bold, Ansi::Red);
$output->send($formatted);

// Spacing helpers
$output->ln(2);        // Returns 2 newlines
$output->indent(3);    // Returns 3 indents (6 spaces)
```

### Disabling ANSI Colors

Pass `--no-ansi` flag to any command to disable color output:

```bash
php haku test --no-ansi
```

> [!NOTE]
> The Output class automatically detects the `--no-ansi` flag and disables formatting accordingly.

---

## Haku\Console\Ansi

Enum providing ANSI escape codes for terminal text formatting and colors.

### Available Styles

**Formatting**
- `Ansi::Bold` — Bold text
- `Ansi::Italic` — Italic text
- `Ansi::Underline` — Underlined text
- `Ansi::Blink` — Blinking text (rarely supported)
- `Ansi::Inverse` — Inverted colors
- `Ansi::Hidden` — Hidden text

**Foreground Colors**
- `Ansi::Black`, `Ansi::Red`, `Ansi::Green`, `Ansi::Yellow`
- `Ansi::Blue`, `Ansi::Magenta`, `Ansi::Cyan`, `Ansi::White`

**Background Colors**
- `Ansi::BlackBackground`, `Ansi::RedBackground`, etc.

### Usage Example

```php
use Haku\Console\Output;
use Haku\Console\Ansi;

$output = new Output();

// Single format
$output->send($output->format('Error', Ansi::Red));

// Multiple formats
$output->send($output->format('Warning', Ansi::Bold, Ansi::Yellow));

// Custom formatting
$styled = Ansi::openTag() . Ansi::Cyan->value . Ansi::closeTag()
    . 'Custom'
    . Ansi::openTag() . Ansi::Off->value . Ansi::closeTag();
```

---

## Helper Functions

**`resolveArguments(): array`**
Parses command-line arguments into a structured array.

```php
use function Haku\Console\resolveArguments;

$args = resolveArguments();
// [
//     'command' => 'test',
//     'arguments' => ['only' => 'UserTest'],
//     'flags' => ['v' => true],
//     'showHelp' => false
// ]
```

**`calculateIndentLength(array $items): int`**
Calculates the maximum string length in an array, useful for aligned output.

```php
use function Haku\Console\calculateIndentLength;

$items = ['short', 'medium', 'very long string'];
$indent = calculateIndentLength($items);  // 16
```

---

## Code Generators

The `make` command provides several built-in generators:

| Generator | Usage | Description |
|-----------|-------|-------------|
| `route` | `php haku make route` | Creates a new route handler |
| `model` | `php haku make model` | Creates a new model class |
| `migration` | `php haku make migration` | Creates a new database migration |
| `middleware` | `php haku make middleware` | Creates a new middleware |
| `spec` | `php haku make spec` | Creates a new test specification |

> [!IMPORTANT]
> When generating a migration from a model, use: `php haku make migration create_model_table --from ModelName`

---

## Advanced Examples

### Custom Command with Validation

```php
namespace App\Commands;

use Haku\Console\Command;

class Deploy extends Command
{
    public function description(): string
    {
        return 'deploys application to production';
    }

    public function options(): array
    {
        return [
            '--env|deployment environment|required',
            '--skip-tests|skip running tests before deploy|',
        ];
    }

    public function invoke(): bool
    {
        if (!isset($this->arguments->arguments['env']))
        {
            $this->output->error('--env is required');
            return false;
        }

        $env = $this->arguments->arguments['env'];
        $skipTests = array_key_exists('skip-tests', $this->arguments->arguments);

        if (!in_array($env, ['staging', 'production']))
        {
            $this->output->error('invalid environment (staging/production)');
            return false;
        }

        if (!$skipTests)
        {
            $this->output->info('running tests...');
            // Run test suite
        }

        $this->output->output(sprintf('deploying to %s...', $env));

        // Deployment logic here

        $this->output->success('deployment complete!');
        return true;
    }
}
```

### Registering Custom Commands

Create a command registry in your bootstrap:

```php
// bootstrap.php
use Haku\Console\Engine;
use App\Commands\{Deploy, Backup, Clean};

if (PHP_SAPI === 'cli')
{
    $engine = new Engine();

    $engine->registerCommand(new Deploy());
    $engine->registerCommand(new Backup());
    $engine->registerCommand(new Clean());

    $engine->run();
}
```

---

## Testing Console Commands

Use Haku's built-in spec testing to test commands:

```php
// vendor/Haku/Console/Commands/Version.spec.php

use function Haku\Spec\{describe, it, expect};
use Haku\Console\Commands\Version;

describe('Version Command', function()
{
    it('should return version string', function()
    {
        $command = new Version();
        $result = $command->invoke();

        expect($result)->toBe(true);
    });
});
```

> [!TIP]
> Run command tests with: `php haku test --only Commands`

---

## Version Management Commands

### `version` — Version Display and Update Check

Displays the current Haku version and automatically checks for updates from the main branch.

**Basic Usage:**
```bash
# Check current version and compare with remote
php haku version

# Skip remote version check
php haku version --no-check
```

**Behavior:**
- By default, fetches the latest version from GitHub and compares it with your local version
- Shows helpful messages about available updates
- Uses a 3-second timeout to avoid hanging on slow connections
- Gracefully handles network failures

**Example Output:**
```bash
[haku]: 0.4.0
[info]: remote is at 0.5.0. Use 'haku upgrade' to download latest.
```

**Options:**
- `--no-check` — Skip checking remote version (useful for offline development or CI/CD)

---

### `upgrade` — Framework Update System

Safely updates the Haku framework from the latest GitHub release with built-in safety features.

**Basic Usage:**
```bash
# Preview changes before applying (recommended)
php haku upgrade --dry-run

# Upgrade with automatic backup
php haku upgrade --backup

# Force upgrade even if versions match
php haku upgrade --force

# Full safe upgrade
php haku upgrade --dry-run --force
php haku upgrade --backup --force
```

**Safety Features:**

1. **Pre-flight Validation** (automatic)
   - Checks PHP version requirements
   - Validates file permissions and disk space
   - Detects uncommitted Git changes
   - Warns about potential issues before proceeding
   - Skip with `--skip-validation` if needed

2. **Version Intelligence**
   - Compares local vs remote versions
   - Prevents accidental downgrades
   - Prevents reinstalling same version
   - Override with `--force` flag

3. **Dry-Run Mode** (`--dry-run`)
   - Preview all changes without applying them
   - Shows detailed file diff (new, modified, unchanged)
   - Displays size changes
   - Perfect for reviewing updates before committing

4. **Automatic Backups** (`--backup`)
   - Creates timestamped backups before upgrading
   - Stored as `private/backup-YYYYMMDD-HHMMSS.zip`
   - Automatic rollback on failure
   - Includes all core framework files

5. **Error Handling**
   - Wraps upgrade in try-catch block
   - Automatic rollback from backup on failure
   - Detailed error messages
   - Safe cleanup of temporary files

**Options:**
- `--dry-run` — Preview changes without applying them
- `--backup` — Create backup before upgrading (recommended)
- `--force` — Force upgrade even if versions are same/newer
- `--skip-validation` — Skip pre-flight validation checks

**Example Workflow:**
```bash
# 1. Check what will change
php haku upgrade --dry-run --force

# 2. Review the output, then upgrade with backup
php haku upgrade --backup --force

# 3. If something goes wrong, restore from backup
# (Backups are stored in private/backup-*.zip)
```

**Example Output:**
```bash
[info]: DRY RUN MODE - No changes will be applied

[haku]: Running pre-flight checks...
[warn]: Git working directory has uncommitted changes.
[ok]: Pre-flight checks passed.

[haku]: downloading latest haku files...
[haku]: extracting source code...

[haku]: Current version: 0.4.0
[haku]: Remote version:  0.5.0

[haku]: Analyzing changes...

[haku]: === UPGRADE SUMMARY ===
[haku]: New files:      3
[haku]: Modified files: 12
[haku]: Unchanged files: 178
[haku]: Total files:    193
[haku]: Size change:    2.5 KB increase

[new]: New files:
[haku]:   + vendor/Haku/Console/Commands/Services/Upgrade/Backup.php (3.21 KB)
[haku]:   + vendor/Haku/Console/Commands/Services/Upgrade/Validation.php (4.87 KB)
[haku]:   + vendor/Haku/Console/Commands/Services/Upgrade/Diff.php (5.15 KB)

[modified]: Modified files:
[haku]:   ~ manifest.json (175 B → 179 B)
[haku]:   ~ vendor/Haku/Console/Commands/Upgrade.php (2.97 KB → 10.21 KB)
[haku]:   ~ vendor/Haku/Console/Commands/Version.php (446 B → 1.85 KB)

[info]: Dry run complete. No changes were applied.
[haku]: Run without --dry-run to apply these changes.
```

**Windows Support:**
Currently, the `upgrade` command is not supported on Windows systems. Unix-based systems (Linux, macOS) are fully supported.

---

## See also

- [[Haku\Spec]] — Testing framework for writing command tests
- [[Haku\Database]] — Database operations used by migrate command
- [[Haku\Delegation]] — Route handling used by routes command
- [[Haku\Filesystem]] — File operations for code generators
