#!/usr/bin/env bash

# Install package
cd /tmp/build
composer require emico/tweakwise-export
cp -R /home/travis/build/EmicoEcommerce/Magento2TweakwiseExport/* /tmp/build/vendor/emico/tweakwise-export
php bin/magento setup:upgrade