#!/usr/bin/env bash

##################################################
# Downloads and installs WordPress for local dev
##################################################

set -euo pipefail

WP_PATH="/app"
WP_URL="https://lolly.lndo.site"
WP_TITLE="Lolly Dev"
PLUGIN_PATH="${WP_PATH}/wp-content/plugins/lolly"
LOG_DIR="/var/log/lolly"

# Check if WordPress is already installed
if wp --path="${WP_PATH}" core is-installed 2>/dev/null; then
    echo "* WordPress already installed. Skipping..."
    exit 0
fi

# Wait for database to be ready
echo "* Waiting for database..."
while ! mysqladmin ping -h"${DB_HOST:-database}" --silent; do
    sleep 1
done

# Download WordPress if not present
if [ ! -f "${WP_PATH}/wp-includes/version.php" ]; then
    echo "* Downloading WordPress..."
    wp core download --path="${WP_PATH}" --version=latest --force
fi

# Create wp-config.php if missing
if [ ! -f "${WP_PATH}/wp-config.php" ]; then
    echo "* Creating wp-config.php..."
    wp config create \
        --path="${WP_PATH}" \
        --dbname="${DB_NAME:-wordpress}" \
        --dbuser="${DB_USER:-wordpress}" \
        --dbpass="${DB_PASSWORD:-wordpress}" \
        --dbhost="${DB_HOST:-database}"

    # Configure log directory (mounted to host for IDE access)
    wp config set LOLLY_LOG_DIR "${LOG_DIR}" --path="${WP_PATH}" --type=constant

    # Enable debug logging to file, not screen
    wp config set WP_DEBUG true --path="${WP_PATH}" --type=constant --raw
    wp config set WP_DEBUG_DISPLAY false --path="${WP_PATH}" --type=constant --raw
    wp config set WP_DEBUG_LOG "${LOG_DIR}/debug.log" --path="${WP_PATH}" --type=constant
fi

# Ensure log directory exists
mkdir -p "${LOG_DIR}"

# Install WordPress
echo "* Installing WordPress..."
wp core install \
    --path="${WP_PATH}" \
    --url="${WP_URL}" \
    --title="${WP_TITLE}" \
    --admin_user=admin \
    --admin_password=password \
    --admin_email=admin@lndo.site \
    --skip-email

# Install plugin composer dependencies (strauss runs via post-install-cmd)
if [ -f "${PLUGIN_PATH}/composer.json" ]; then
    echo "* Installing plugin dependencies..."
    cd "${PLUGIN_PATH}" && /usr/local/bin/composer install --no-interaction
fi

# Activate the plugin
echo "* Activating lolly plugin..."
wp --path="${WP_PATH}" plugin activate lolly

echo "* Done! Visit ${WP_URL}/wp-admin (admin/password)"
