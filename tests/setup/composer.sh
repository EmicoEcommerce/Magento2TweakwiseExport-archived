#!/usr/bin/env bash

# Configure composer auth
if [ -n "$GITHUB_TOKEN" ]; then
    echo "Configure composer github token";
    composer config --global --auth github-oauth.github.com ${GITHUB_TOKEN};
fi;
if [ -n "$MAGENTO_TOKEN_PRIVATE" ]; then
    echo "Configure composer magento token";
    composer --global config --auth http-basic.repo.magento.com ${MAGENTO_TOKEN_PUBLIC} ${MAGENTO_TOKEN_PRIVATE};
fi;

composer install