#!/usr/bin/env bash

# Configure composer auth
if [ -n "$GITHUB_TOKEN" ]; then composer config github-oauth.github.com ${GITHUB_TOKEN}; fi;
if [ -n "$MAGENTO_TOKEN_PRIVATE" ]; then composer config http-basic.repo.magento.com ${MAGENTO_TOKEN_PUBLIC} ${MAGENTO_TOKEN_PRIVATE}; fi;

composer install