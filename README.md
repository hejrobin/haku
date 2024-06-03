# Haku

A tiny web application framework with big ambitions.

#### Core Concepts

1. **Native Features First** <br /> Haku aims to have a small footprint, without any external dependencies and relies soley on the power of native PHP 8.3 features.
2. **Developer Friendly** <br /> Haku ships with a command line tool that helps you generate code, run code tests and set up a development server all from your terminal of choice.
3. **Sprouting Codebase** <br /> Haku might have a limited feature-set out of the box, but does provide a great foundation to build and add bespoke libraries and components.

---

## Getting Started

#### Prerequisites

* [PHP >= 8.3](https://www.php.net/releases/8.3/en.php)
* [PDO PHP Extension](https://www.php.net/manual/en/book.pdo.php)
* [mbstring](https://www.php.net/manual/en/ref.mbstring.php)

### Installing Haku

```sh

gh repo clone hejrobin/haku project-name

# or via ssh
# git clone git@github.com:hejrobin/haku.git project-name

cd project-name

php haku init --dev

```

---

## Haku Command Line Tools

Haku ships with its own command line tool, `haku` which provides some helpful commands to aid you in the development of your project. You can always run `haku --help` to see what commands are available.

#### Available Commands

* `php haku init` — Creates required configuration files, if you add `--dev` or `--test` configuration files for those environments will be created.
* `php haku serve` — Starts a development server using [PHP's built in server](https://www.php.net/manual/en/features.commandline.webserver.php).
* `php haku make <generator>` — Invokes one of the code generators that Haku ships with, run `php haku make --help` to see available generators.
* `php haku test` — Runs all available `*.spec.php` tests in the workspace, you can control what tests to run with the flags `--only` or `--omit`.
* `php haku version` — Shows current haku version.
* `php haku routes` — Lists all available routes based on defined application routes.

---

### Planned Features

* Database Migrations
* Framework Upgrade Commands
