#!/usr/bin/env bash

# Install Magento with dependencies
cd /tmp
composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition:${MAGE2_VERSION} build
cd build
composer require emico/tweakwise-export
cp -R /home/travis/build/EmicoEcommerce/Magento2TweakwiseExport/* /tmp/build/vendor/emico/tweakwise-export
php bin/magento setup:install --admin-email "$MAGE2_ADMIN_EMAIL" --admin-firstname "$MAGE2_ADMIN_FIRST_NAME" --admin-lastname "$MAGE2_ADMIN_LAST_NAME" --admin-password "$MAGE2_ADMIN_PASSWORD" --admin-user "$MAGE2_ADMIN_USERNAME" --backend-frontname admin --base-url "$MAGE2_FAKE_URL" --db-host "$MAGE2_DB_HOST" --db-name "$MAGE2_DB_NAME" --db-user "$MAGE2_DB_USER" --db-password "$MAGE2_DB_PASSWORD" --session-save files --use-rewrites 1 --use-secure 0 -vvv
php bin/magento sampledata:deploy
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean