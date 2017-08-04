#!/usr/bin/env bash

# Install Magento with dependencies
git clone --depth=10 -b $MAGE2_VERSION https://github.com/magento/magento2 /tmp/build
cd /tmp/build
composer require emico/tweakwise-export
composer install
cp -R /home/travis/build/EmicoEcommerce/Magento2TweakwiseExport/* /tmp/build/vendor/emico/tweakwise-export
php bin/magento setup:install --admin-email "$MAGE2_ADMIN_EMAIL" --admin-firstname "$MAGE2_ADMIN_FIRST_NAME" --admin-lastname "$MAGE2_ADMIN_LAST_NAME" --admin-password "$MAGE2_ADMIN_PASSWORD" --admin-user "$MAGE2_ADMIN_USERNAME" --backend-frontname admin --base-url "$MAGE2_FAKE_URL" --db-host "$MAGE2_DB_HOST" --db-name "$MAGE2_DB_NAME" --db-user "$MAGE2_DB_USER" --db-password "$MAGE2_DB_PASSWORD" --session-save files --use-rewrites 1 --use-secure 0 -vvv
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean