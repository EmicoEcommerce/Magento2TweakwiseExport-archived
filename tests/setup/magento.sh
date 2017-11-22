#!/usr/bin/env bash
set -e

# Install Magento with dependencies
echo "Create magento project"
cd /tmp
composer create-project --no-interaction --ignore-platform-reqs --repository-url=https://repo.magento.com/ magento/project-community-edition:${MAGE2_VERSION} build
cd build

cp -vf ${TRAVIS_BUILD_DIR}/tests/setup/install-config-mysql.php dev/tests/integration/etc/