# Haku ![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/hejrobin/haku/spec.yml)
<sup>A tiny web application framework with big ambitions.</sup>

Haku is a small, opinionated web application framework built for JSON APIs. It aims to have a small footprint without any external dependencies or extensions, relying on native PHP 8.4 features.

-----

## Getting Started

> [!NOTE]
> Make sure you have [PHP 8.4 or newer](https://www.php.net/releases/8.4/en.php) installed with [mbstring](https://www.php.net/manual/en/ref.mbstring.php) and [PDO PHP Extension](https://www.php.net/manual/en/book.pdo.php).

### Installing Haku

```sh

gh repo clone hejrobin/haku project-name

# or via ssh
# git clone git@github.com:hejrobin/haku.git project-name

cd project-name

php haku-init

```

-----

## Features & Philosophies

### ✨ Native Features First

Haku aims to have a small footprint, without any external dependencies and relies soley on the power of native [PHP 8.4 features](https://www.php.net/releases/8.4/en.php).

### ✨ Testing Is Built In

Haku ships with a simple test/spec runner inspired by [Jest](https://jestjs.io/). Writing tests for your code should be quick and easy, Haku tries to help with that.

### ✨ Useful Tooling

Ships with a tiny command line interface, a built in development server and code generators.

#### 🐉 Framework Commands

| Command | Description |
| --- | --- |
| `php haku-init`				| Initial setup, creates required configuration files |
| `php haku env` 				| Creates, or refreshes an already defined environment |
| `php haku version`		| Shows current haku version. And if framework needs upgrade |
| `php haku upgrade` 		| Downloads and upgrades current framework with latest release. |
| `php haku release` 		| Makes a new Haku release, support bumping with `--major`, `--minor` or `--patch`. |
| `php haku okidoki` 		| Generates documentation for Haku packages. |

#### 👷 Development Commands

| Command | Description |
| --- | --- |
| `php haku serve`			| Starts a development server using [PHP's built in server](https://www.php.net/manual/en/features.commandline.webserver.php). |
| `php haku routes` 		| Lists all available routes based on defined application routes. |
| `php haku test`				| Runs all available `*.spec.php` tests in the workspace, you can control what tests to run with the flags `--only` or `--omit`. |
| `php haku migrate`		| Runs any viable migration, default to up, can be downgraded with `--down`. If `--seed` is passed, it will also run seed. |
| `php haku make`				| Invokes one of the code generators that Haku ships with, run `php haku make --help` to see available generators. |

> [!TIP]
> If you need any help with commands, just add `--help` and it'll output documentation for each command. For more help, see [Commands](COMMANDS.md)

-----

## FAQ

#### ❇️ Why doesn't Haku use Composer?

I wanted to build a framework that has a "_batteries included_" kind of philosophy, without external dependencies, just the bare minimum needed to create a JSON API.
  
While [Composer](https://getcomposer.org/) is an amazing and convenient tool, it isn't really the approach I wanted for Haku.

#### ❇️ Does Haku follow any coding standards guidelines?

Yes, Haku follows [PSR-1](https://www.php-fig.org/psr/psr-1/). 

-----

👋🏻 Happy coding!
