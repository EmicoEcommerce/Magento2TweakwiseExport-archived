#!/usr/bin/env bash
set -e

# Install package
cd /tmp/build
composer require --no-interaction --ignore-platform-reqs emico/tweakwise-export
cp -R ${TRAVIS_BUILD_DIR}/* /tmp/build/vendor/emico/tweakwise-export

php bin/magento module:enable Emico_TweakwiseExport
php bin/magento setup:upgrade --no-interaction

# Install package dev dependencies. Unfortunately I do not have a generic way to do this yet
composer require fzaninotto/faker --no-interaction

# Make sure auto loading works as expected
composer dump-autoload