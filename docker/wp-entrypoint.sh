#!/bin/bash
set -e

if wait-for-it.sh "${WORDPRESS_DB_HOST}" -t 60; then
  docker-entrypoint.sh apache2 -v
  wp core install \
    --allow-root \
    --title="${WP_TITLE}" \
    --admin_user="${ADMIN_USER}" \
    --admin_password="${ADMIN_PASS}" \
    --url="${WP_DOMAIN}" \
    --admin_email="${ADMIN_EMAIL}" \
    --skip-email
  wp plugin is-installed akismet --allow-root && wp plugin uninstall akismet --allow-root --path="${DOCROOT_PATH}"
  wp plugin is-installed hello --allow-root && wp plugin uninstall hello --allow-root --path="${DOCROOT_PATH}"

  # wp plugin is-active returns 0 if plugin is active, 1 otherwise https://developer.wordpress.org/cli/commands/plugin/is-active/
  wp plugin is-active "${PLUGIN_NAME}" && wp plugin activate "${PLUGIN_NAME}" --allow-root --path="${DOCROOT_PATH}"

fi

exec "$@"
