#!/usr/bin/env bash
set -e

# Ensure database exists
mysql -e "CREATE DATABASE IF NOT EXISTS ${MAGE2_DB_NAME};"
mysql -e "CREATE USER ${MAGE2_DB_USER}@${MAGE2_DB_HOST} IDENTIFIED BY '${MAGE2_DB_PASSWORD}';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO ${MAGE2_DB_USER}@${MAGE2_DB_HOST};"