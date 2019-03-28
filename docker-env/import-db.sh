#!/usr/bin/env bash
PROJECT_NAME='e20r-member-directory-for-pmpro'
echo "Importing database for ${PROJECT_NAME}"
sleep 30;
echo $(pwd)
make wp db import ./mariadb-init/${PROJECT_NAME}.sql
