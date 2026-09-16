#!/usr/bin/env bash
# Gives the test suite a database of its own, so running the tests never wipes
# the demo data someone is looking at.
mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS testing;
    GRANT ALL PRIVILEGES ON \`testing%\`.* TO '$MYSQL_USER'@'%';
EOSQL
