#!/usr/bin/env bash

# Variables to indicate key settings files or directories for Drupal.
DRUPAL_ROOT=/var/www/html/web
DRUPAL_SETTINGS_FILE=$DRUPAL_ROOT/sites/default/settings.php
DRUPAL_SERVICES_FILE=$DRUPAL_ROOT/sites/default/services.yml

# If we don't have a Drupal install, download it.
if [ ! -d "/var/www/html/web/core" ]; then
  echo "Installing Drupal"
  export COMPOSER_PROCESS_TIMEOUT=600
  composer install
fi

# Create Drupal public files directory and set IO permissions.
if [ ! -d "/var/www/html/web/files" ]; then
  echo "Creating public Drupal files directory"
  mkdir -p /var/www/html/web/files
  chmod -R 0775 /var/www/html/web/files
fi

# Create Drupal private file directory above web root.
if [ ! -d "/var/www/html/private" ]; then
  echo "Creating private Drupal files directory"
  mkdir -p /var/www/html/private
fi

if [ ! -d $DRUPAL_ROOT/sites/default/settings.local.php ]; then
  echo "Creating local Drupal settings and developent services files"
  cp -v /var/www/html/.ddev/homeadditions/drupal.settings.php $DRUPAL_ROOT/sites/default/settings.local.php
  cp -v /var/www/html/.ddev/homeadditions/drupal.services.yml $DRUPAL_ROOT/sites/local.development.services.yml
fi

# Create DDev 'app' link for mapping web/sites entries to the project/sites dir.
echo "Creating App symlink "
cd /
sudo ln -sf /var/www/html /app
