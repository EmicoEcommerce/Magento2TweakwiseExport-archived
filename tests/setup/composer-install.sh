#!/usr/bin/env bash
set -e

# Configure composer auth
composer global require hirak/prestissimo
composer install --ignore-platform-reqs