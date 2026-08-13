# Laravel Starter

## Installation

`cp .env.example .env`

`sail artisan key:generate`

`sail artisan boost:install`

## Development

`sail up -d` — web server only.

`sail artisan dev` — horizon, scheduler, logs and vite in one TUI. The process list lives in `AppServiceProvider::registerDevCommands()`.
