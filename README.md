# bssb.app

🌐 **Website and API application powering the Beat Saber Server Browser mod (https://bssb.app)**

[![Build and Deploy](https://github.com/roydejong/bssb.app/workflows/Build%20and%20Deploy/badge.svg)](https://github.com/roydejong/bssb.app/actions?query=workflow%3A%22Build+and+Deploy%22)

## Overview

This is the PHP source code powering the API server that is used
by [BeatSaberServerBrowser](https://github.com/roydejong/BeatSaberServerBrowser).

### Features

- 📝 Receives and stores game information from players hosting matches
- 😎 Lets mod users browse and filter compatible and relevant games
- 🌐 Provides a public site where you can view games and stats

## Setup

### Requirements

- Web server (recommended: nginx)
- PHP 8.3+ with extensions:
    - `curl`, `dom`, `json`, `mbstring`, `pdo`, `xml`
- [Composer](https://getcomposer.org/)
- MySQL (or compatible) database

### Installation

Clone the repository (suggested path: `/var/www/bssb.app`) and install the dependencies with Composer:

```bash
composer install --no-dev
```

Configure nginx to direct all requests to `public/index.php`, example config:

```nginx
server {
    server_name bssb.app;
    root /var/www/bssb.app/public;

    autoindex off;
    server_tokens off;

    index index.php;
    error_page 404 = /index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi.conf;
    }
}
``` 

Seed or migrate the database:

```bash
vendor/bin/phinx migrate
```

### Configuration

Create a `config.php` in the application root directory, using the provided `config.sample.php` as a template:

| Setting                   | Description                                                                                                                                |
|---------------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| Sentry DSN                | To enable error reporting to [Sentry](https://sentry.io/welcome/), set this to your Data Source Name (DSN).                                |
| `cache_enabled`           | Enable compilation / view caching. Recommended for production.                                                                             |
| `response_cache_enabled`  | Enable caching of web / API responses. Recommended for production.                                                                         |
| `hashids_salt`            | Salt for calculating hashids. Randomize to prevent iteration of IDs in URLs.                                                               |
| `steam_web_api_key`       | [Steam Web API Key](https://steamcommunity.com/dev) for authenticating users via Steam. If empty, Steam integration will not work.         |
| `master_server_blacklist` | Array of master server hosts to block/ignore announce messages from. Defaults to none.                                                     |
| `allow_multiple_results`  | Controls whether match results should be locked after receiving them the first time. Defaults to `false`.                                  |
| `allow_boring`            | Controls whether games that are unlikely to be relevant should be allowed (games announced from LAN, localhost, etc). Defaults to `false`. |
| `twitter_api_key`         | Optional Twitter API Key for automating news posts to Twitter.                                                                             |
| `twitter_api_key_secret`  | Optional Twitter API Secret Key for automating news posts to Twitter.                                                                      |
| dbConfig                  | [Instarecord database configuration](https://github.com/SoftwarePunt/instarecord?tab=readme-ov-file#configuration).                        |

### Cron

To enable cron jobs, by adding the following line to the crontab:

```
* * * * * cd /var/www/bssb.app && vendor/bin/crunz schedule:run
```
