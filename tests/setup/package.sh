#!/usr/bin/env bash

# Install package
cd /tmp/build
composer require --no-interaction --ignore-platform-reqs emico/tweakwise-export
cp -R ${TRAVIS_BUILD_DIR}/* /tmp/build/vendor/emico/tweakwise-export
php bin/magento setup:upgrade --no-interaction