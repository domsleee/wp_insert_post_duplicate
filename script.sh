#!/usr/bin/env bash
set -ex;
wp --allow-root core install --url=http://localhost:8080/wordpress \
  --title=Example \
  --admin_user=supervisor \
  --admin_password=strongpassword \
  --admin_email=info@example.com;
wp --allow-root plugin activate my_plugin;
wp --allow-root my_plugin;