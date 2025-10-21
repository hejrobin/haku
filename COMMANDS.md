# Haku Commands

> [!NOTE]
> All commands, except `haku-init` must prefixed with `php haku`.

-----

## 🐉 Framework Commands

### `haku-init`

This command is required with a fresh install of Haku. It'll create `dev` and `test` environment configuration files.

```sh
php haku-init
```

### `env`

Used to create, or regenerate environment configurations.

```sh
php haku env --name prod
```

| Flag | Description |
| --- | --- |
| `--name`					| Required when making a new or regenerating an existing env |
| `--regenderate`		|	If provided, it will regenerate config for specified env |


### `version`

Returns local and remote Haku versions.

```sh
php haku version
```

| Flag | Description |
| --- | --- |
| `--no-check` | If provided, it will only show the local version |


### `upgrade`

Upgrades Haku (if needed) from latest known release.

```sh
php haku upgrade
```

| Flag | Description |
| --- | --- |
| `--dry-run`					| Preview changes without applying them |
| `--backup`  				| Creates a backup before upgrading |
| `--skip-validation` | Skip pre-upgrade validation checks |
| `--force 						| Force update even if local and remote version matches |


### `release`

Creates a new release for Haku, bumps version and creates a chore-commit and updates CHANGELOG.

```sh
php haku release --major --message "New version!"
```

| Flag | Description |
| --- | --- |
| `--major`          | Create major release (x.0.0) |
| `--minor`          | Create minor release (0.x.0) |
| `--patch`          | Create patch release (0.0.x) |
| `--set`            | Set version manually |
| `--from`           | Git reference to start changelog from (tag/commit) |
| `--message`        | Custom message to display in changelog |
| `--no-changelog`   | Skip changelog generation |


### `okidoki`

Runs through and creates README files for Haku packages.

```sh
php haku okidoki
```

-----

## 👷 Development Commands

### `serve`

Starts a development server using [PHP's built in server](https://www.php.net/manual/en/features.commandline.webserver.php).


### `routes`

Lists all available routes based on defined application routes.

| Flag | Description |
| --- | --- |
| `--inspect` | Output routes as a printed tree |
| `--postman` | Generates a [Postman](https://www.postman.com/) collection file |


### `test`

Runs all available `*.spec.php` tests in the workspace, you can control what tests to run with the flags `--only` or `--omit`. 

| Flag | Description |
| --- | --- |
| `--only`           | Runs test matching filter |
| `--omit`           | Runs all tests except filter |
| `--tags`           | Runs tests with matching tags (comma-separated) |
| `--exclude-tags`   | Excludes tests with matching tags (comma-separated) |


### `migrate`

Runs any viable migration, default to up, can be downgraded with `--down`. If `--seed` is passed, it will also run seed.

```sh
php haku migrate
```

| Flag | Description |
| --- | --- |
| `--down` | Reverts the last migration |
| `--seed` | Runs seed method after migrations |


### `make {generator}`

Invokes one of the code generators that Haku ships with.

> [!WARNING]
> Generators currently don't have a proper `--help` output, and may still need some additional work.

```sh
php haku make {generator}
```

#### Available Generators

| Generator | Description |
| --- | --- |
| `middleware`		| Creates a middleware in `app/middlewares` |
| `migration` 		| Creates a migration in `app/migrations` |
| `model` 				| Creates a model in `app/models` |
| `route` 				| Creates a route in `app/routes` |
| `spec` 					| Creates a spec file |
