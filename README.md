# Haku
![Haku Development Status](https://img.shields.io/badge/in%20development-007580) ![GitHub Actions Workflow Status](https://img.shields.io/github/actions/workflow/status/hejrobin/haku/spec.yml)

A tiny web application framework with big ambitions.

> [!IMPORTANT]
> Haku is still in development, and is not meant for production use right now.

---

Haku is a small, opinionated web application framework built for JSON APIs. It aims to have a small footprint without any external dependencies or extensions, relying on native PHP 8.3 features.

## Getting Started

#### What to expect

Haku aspires to work "out-of-the-box" and doesn not use Composer. It is built to quickly build small to medium sized RESTful JSON APIs.

#### Prerequisites

* [PHP >= 8.3](https://www.php.net/releases/8.3/en.php)
* [mbstring](https://www.php.net/manual/en/ref.mbstring.php)
* [PDO PHP Extension](https://www.php.net/manual/en/book.pdo.php)

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

| Command | Description |
| --- | --- |
| `php haku init`|  Creates required configuration files, if you add `--dev` or `--test` configuration files for those environments will be created.
| `php haku serve`|  Starts a development server using [PHP's built in server](https://www.php.net/manual/en/features.commandline.webserver.php).
| `php haku make <generator>`|  Invokes one of the code generators that Haku ships with, run `php haku make --help` to see available generators.
| `php haku test`|  Runs all available `*.spec.php` tests in the workspace, you can control what tests to run with the flags `--only` or `--omit`.
| `php haku version`| Shows current haku version.
| `php haku routes` | Lists all available routes based on defined application routes.


### Updating your Haku project

> [!CAUTION]
> The upgrade command is currently disabled for *Windows* and untested in **nix* environments.

If you've forked Haku to use in your project, sometimes you might want to upgrade to the latest version. This requires [PHP ZIP Extension](https://www.php.net/manual/en/class.ziparchive.php), and then you can run `php haku upgrade` to fetch the latest repository changes available.
