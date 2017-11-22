#!/usr/bin/env bash
set -e

# Install Magento with dependencies
echo "Create magento project"
cd /tmp
composer create-project --no-interaction --ignore-platform-reqs --repository-url=https://repo.magento.com/ magento/project-community-edition:${MAGE2_VERSION} build
cd build

cp -vf ${TRAVIS_BUILD_DIR}/tests/setup/install-config-mysql.php dev/tests/integration/etc/

echo "Install magento"
php bin/magento setup:config:set --no-interaction \
    --backend-frontname admin \
    --db-host "$MAGE2_DB_HOST" \
    --db-name "$MAGE2_DB_NAME" \
    --db-user "$MAGE2_DB_USER" \
    --db-password "$MAGE2_DB_PASSWORD" \
    --session-save=files \
    -vvv