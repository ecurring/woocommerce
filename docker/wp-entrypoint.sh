#!/bin/bash
set -e

if wait-for-it.sh "${WORDPRESS_DB_HOST}" -t 60; then
  docker-entrypoint.sh apache2 -v

  # WP_CLI is-* commands returns 0 if plugin is active, 1 otherwise
  # https://developer.wordpress.org/cli/commands/plugin/is-active/
  # https://developer.wordpress.org/cli/commands/core/is-installed/
  wp core is-installed --allow-root || \
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

  wp plugin is-active --allow-root "${PLUGIN_NAME}" || wp plugin activate --allow-root "${PLUGIN_NAME}"  --path="${DOCROOT_PATH}"

fi

exec "$@"
