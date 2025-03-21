#!/bin/sh

# Wait for migrate success
until php vendor/bin/phinx migrate; do
  echo "Waiting for MySQL / Phinx status success..."
  sleep 2
done

# Delete cache
rm -rf /var/www/html/storage/cache/*
rm -rf /var/www/html/storage/response_cache/*

# Run base command
exec "$@"