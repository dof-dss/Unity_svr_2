<?php

/**
 * @file
 * Default Drupal 8 settings.
 *
 * These are already explained with detailed comments in Drupal's
 * default.settings.php file.
 * See https://api.drupal.org/api/drupal/sites!default!default.settings.php/8
 */

$databases = [];
$settings['update_free_access'] = FALSE;
$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

// Site hash salt.
$settings['hash_salt'] = getenv('HASH_SALT');

// Temp directory.
$settings["file_temp_path"] = getenv('FILE_TEMP_PATH') ?? '/tmp';

// Set config split environment; environment specific values
// is set near the end of this file.
$config['config_split.config_split.local']['status'] = FALSE;
$config['config_split.config_split.hosted']['status'] = FALSE;
$config['config_split.config_split.production']['status'] = FALSE;

// Config readonly settings; default to active if not specified.
$settings['config_readonly'] = !empty(getenv('CONFIG_READONLY')) ? getenv('CONFIG_READONLY') : 1;

// Permit changes via command line.
if (PHP_SAPI === 'cli') {
  $settings['config_readonly'] = 0;
}

// Configuration that is allowed to be changed in readonly environments.
$settings['config_readonly_whitelist_patterns'] = [
  'system.site',
];

// Detect site id as $subsite_id from sites/site_id/settings.php.
if (!empty($subsite_id)) {
  // Convert it to uppercase as that's our format for ENV vars
  // eg: UREGNI_GOOGLE_MAP_API_KEY.
  $site_id = strtoupper($subsite_id);
  $settings['subsite_id'] = $site_id;

  // Geolocation module API keys.
  $config['geolocation_google_maps.settings']['google_map_api_key'] = getenv($site_id . '_' . 'GOOGLE_MAP_API_KEY');
  $config['geolocation_google_maps.settings']['google_map_api_server_key'] = getenv($site_id . '_' . 'GOOGLE_MAP_API_SERVER_KEY');
  // Geocoder module API key.
  $config['geocoder.settings']['plugins_options']['googlemaps']['apikey'] = getenv($site_id . '_' . 'GOOGLE_MAP_API_KEY');
}

// Environment indicator defaults.
$env_colour = !empty(getenv('SIMPLEI_ENV_COLOR')) ? getenv('SIMPLEI_ENV_COLOR') : '#000000';
$env_name = !empty(getenv('SIMPLEI_ENV_NAME')) ? getenv('SIMPLEI_ENV_NAME') : getenv('PLATFORM_BRANCH');

// If we're running on platform.sh, check for and load relevant settings.
if (!empty(getenv('PLATFORM_BRANCH'))) {

  if (file_exists($app_root . '/' . $site_path . '/../settings.platformsh.php')) {
    include $app_root . '/' . $site_path . '/../settings.platformsh.php';
  }

  // Use 'hosted' config split for all Platform.sh sites apart from production.
  $config['config_split.config_split.hosted']['status'] = TRUE;

  // Environment specific settings and services.
  switch (getenv('PLATFORM_BRANCH')) {
    case 'main':
      // De-facto production settings.
      $settings['container_yamls'][] = $app_root . '/' . $site_path . '/../services.yml';
      // Use 'production' config split for the Platform.sh production site.
      $config['config_split.config_split.production']['status'] = TRUE;
      $config['config_split.config_split.hosted']['status'] = FALSE;
      break;

    case 'D8UN-edge':
      // Edge environment orange toolbar.
      $env_colour = '#e56716';
      break;

    case 'D8UN-uat':
      // UAT environment purple toolbar.
      $env_colour = '#9370DB';
      break;

    default:
      $settings['container_yamls'][] = $app_root . '/' . $site_path . '/../development.services.yml';
      include $app_root . '/' . $site_path . '/../settings.development.php';
  }
  $settings['simple_environment_indicator'] = sprintf('%s %s', $env_colour, $env_name);
}

if (getenv('LANDO') && file_exists($app_root . '/' . $site_path . '/../settings.lando.php')) {
  include $app_root . '/' . $site_path . '/../settings.lando.php';
}

// Configure file paths.
if (!isset($settings['file_public_path'])) {
  $settings['file_public_path'] = 'files/' . $subsite_id;
}

// Set up a config sync directory.
//
// This is defined inside the read-only "config" directory, deployed via Git.
$settings['config_sync_directory'] = '../config/' . $subsite_id . '/config';

// Local settings. These come last so that they can override anything.
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
