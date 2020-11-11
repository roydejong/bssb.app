# bssb.app
🌐 **Website and API application powering the Beat Saber Server Browser mod (https://bssb.app)**

![Build and Deploy](https://github.com/roydejong/bssb.app/workflows/Build%20and%20Deploy/badge.svg)

## Overview

This is the PHP source code powering the API server that is used by [BeatSaberServerBrowser](https://github.com/roydejong/BeatSaberServerBrowser). It provides the following functionality:

- 📝 Receives and stores game information from players hosting matches
- 😎 Lets mod users browse and filter compatible and relevant games
- 🌐 Provides a basic public site where you can view games and stats

## Goals

My goal for this project is to keep it fast and lean. It primarily just passes information back and forth, so there's no need for any complex architecture. There's some specific goals I set for this project:

- Minimal code
- Minimal dependencies
- Cache everything

Bottom line: I want to be able to run this on a $5 droplet and still get good performance out of it. Okay, maybe $10.

## Setting up

### Requirements
- PHP 7.4+ with `ext-json`
- [Composer](https://getcomposer.org/)

### Installation

1. Clone the repository and run `composer install`.
2. Configure your web server to direct all requests to `public/index.php`. For nginx this looks something like this:

    ```nginx
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    ```

## Notes / FAQs

### Live deployment
🚧 **Under construction, this is not actually live yet** 🚧

Succesful builds on this repository will be automatically deployed to the live site at https://bssb.app.

### Why is the API server needed?
There is no offical way to ask the Beat Saber servers for a list of ongoing games. There *may* be an undocumented way of doing this, but even so, we would be missing out on valuable metadata.

Additionally, cross-play mods cause players to be fragmented across official and unofficial master servers. We need a neutral API that exists outside of them, so you can see games on other master servers too (the mod handles server switching).
