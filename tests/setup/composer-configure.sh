#!/usr/bin/env bash
set -e

# Configure composer auth
if [ -n "$GITHUB_TOKEN" ]; then
    echo "Configure composer github token";
    composer config $1 --auth github-oauth.github.com ${GITHUB_TOKEN};
fi;
if [ -n "$MAGENTO_TOKEN_PRIVATE" ]; then
    echo "Configure composer magento token";
    composer config $1 --auth http-basic.repo.magento.com ${MAGENTO_TOKEN_PUBLIC} ${MAGENTO_TOKEN_PRIVATE};
fi;