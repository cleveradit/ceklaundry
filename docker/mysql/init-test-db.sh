#!/bin/sh
set -eu
MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot <<SQL
CREATE DATABASE IF NOT EXISTS ceklaundry_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_ci;
GRANT ALL PRIVILEGES ON ceklaundry_test.* TO '${MYSQL_USER}'@'%';
SQL
