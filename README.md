# Laravel Starter

## Installation

`cp .env.example .env`

`sail artisan key:generate`

`sail artisan boost:install`

## Development

`sail up -d` — web server only.

`sail artisan dev` — horizon, scheduler, logs and vite in one TUI. The process list lives in `AppServiceProvider::registerDevCommands()`.

**PhpStorm interpreter.** Use the Docker Compose interpreter with the configuration files
`./docker-compose.yml` **and** `./docker-compose.phpstorm.yml`, in that order, and the
*Always start a new container* lifecycle. The second file runs tools as your host uid, so
the IDE stops leaving root-owned files in `storage/`, `vendor/` and the container's `/tmp`.
