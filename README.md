# bssb.app
🌐 **Website and API application powering the Beat Saber Server Browser mod (https://bssb.app)**

[![Build and Deploy](https://github.com/roydejong/bssb.app/workflows/Build%20and%20Deploy/badge.svg)](https://github.com/roydejong/bssb.app/actions?query=workflow%3A%22Build+and+Deploy%22)

## Overview

This is the PHP source code powering the API server that is used by [BeatSaberServerBrowser](https://github.com/roydejong/BeatSaberServerBrowser).

### Features

- 📝 Receives and stores game information from players hosting matches
- 😎 Lets mod users browse and filter compatible and relevant games
- 🌐 Provides a public site where you can view games and stats

## Installation

### Requirements
- Web server (recommended: nginx)
- PHP 8.1+ with extensions:
   - `curl`, `dom`, `json`, `mbstring`, `pdo`, `xml`
- [Composer](https://getcomposer.org/)
- MySQL (or compatible) database server

### Installation

1. Clone the repository and run `composer install --no-dev` (suggested path: `/var/www/bssb.app`).
2. Configure your web server to direct all requests to `public/index.php`, nginx sample:

    ```nginx
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    ```
3. Seed or migrate the database:

    ```
    vendor/bin/phinx migrate
    ```

6. Create a `config.php` in the application root directory, using the provided `config.sample.php`.
7. Enable cron jobs, by adding the following line to the `crontab`:

    ```
    * * * * * cd /var/www/bssb.app && vendor/bin/crunz schedule:run
    ```
