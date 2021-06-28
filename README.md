# bssb.app
🌐 **Website and API application powering the Beat Saber Server Browser mod (https://bssb.app)**

[![Build and Deploy](https://github.com/roydejong/bssb.app/workflows/Build%20and%20Deploy/badge.svg)](https://github.com/roydejong/bssb.app/actions?query=workflow%3A%22Build+and+Deploy%22)

## Overview

This is the PHP source code powering the API server that is used by [BeatSaberServerBrowser](https://github.com/roydejong/BeatSaberServerBrowser).

### Features

- 📝 Receives and stores game information from players hosting matches
- 😎 Lets mod users browse and filter compatible and relevant games
- 🌐 Provides a basic public site where you can view games and stats

## Installation

### Requirements
- Web server (recommended: nginx)
- PHP 8.0+ with extensions:
   - `curl`, `dom`, `json`, `mbstring`, `pdo`, `xml`
- [Composer](https://getcomposer.org/)
- MySQL (or compatible) database server

### Installation

1. Clone the repository and run `composer install --no-dev`.
2. Configure your web server to direct all requests to `public/index.php`, nginx sample:

    ```nginx
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    ```
3. Create a database, using the provided `db_structure.sql`.
4. Create a `config.php` in the application root directory, using the provided `config.sample.php`.

## Development

### Live deployment
Successful builds on this repository's `main` branch will be automatically deployed to the live site at https://bssb.app.

### Goals
My goal for this project is to keep it fast and lean. It primarily just passes information back and forth, so there's no need for any complex architecture. There's some specific goals I set for this project:

- Minimal code
- Minimal dependencies
- Cache everything

Bottom line: I want to be able to continue to run this on a $5 droplet.