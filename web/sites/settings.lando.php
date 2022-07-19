<?php

/**
 * @file
 * Lando local development override configuration file.
 */

use Drupal\Core\Installer\InstallerKernel;

$databases['default']['default'] = [
  'database' => $subsite_id,
  'username' => getenv('DB_USER'),
  'password' => getenv('DB_PASS'),
  'prefix' => getenv('DB_PREFIX'),
  'host' => getenv('DB_HOST'),
  'port' => getenv('DB_PORT'),
  'namespace' => getenv('DB_NAMESPACE'),
  'driver' => getenv('DB_DRIVER'),
];

$databases[$subsite_id . '7']['default'] = [
  'database' => $subsite_id . '_legacy',
  'username' => getenv('DB_USER'),
  'password' => getenv('DB_PASS'),
  'prefix' => getenv('DB_PREFIX'),
  'host' => getenv('DB_HOST'),
  'port' => getenv('DB_PORT'),
  'namespace' => getenv('DB_NAMESPACE'),
  'driver' => getenv('DB_DRIVER'),
];

// Prevent SqlBase from moaning.
$databases['migrate']['default'] = $databases[$subsite_id . '7']['default'];

$settings["file_temp_path"] = getenv('FILE_TEMP_PATH') ?? '/tmp';
$settings['file_private_path'] = getenv('FILE_PRIVATE_PATH');

// Add trusted host pattern for Lando sites, in Lando config.
$settings['trusted_host_patterns'][] = '^.+\.lndo\.site$';

// Set stage file proxy config, if there's an envvar to support it.
if (!empty(getenv('STAGE_FILE_PROXY_ORIGIN'))) {
  $config['stage_file_proxy.settings']['origin'] = getenv('STAGE_FILE_PROXY_ORIGIN');
}

// Assume all Lando sites should use 'local' config for devlopment.
$config['config_split.config_split.local']['status'] = TRUE;
$config['config_split.config_split.hosted']['status'] = FALSE;

// Environment indicator config.
$settings['simple_environment_indicator'] = sprintf('%s %s', getenv('SIMPLEI_ENV_COLOUR'), getenv('SIMPLEI_ENV_NAME'));

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/../local.services.yml';

// Redis Cache.
// Due to issues with enabling Redis during install/config import. We cannot enable the cache backend by default.
// Once you have a site/db installed. Enable the Redis module and change the $redis_enabled to true.
$redis_enabled = FALSE;
if ($redis_enabled && !InstallerKernel::installationAttempted() &&
    extension_loaded('redis') && class_exists('Drupal\redis\ClientFactory')) {
  $settings['redis.connection']['interface'] = 'PhpRedis';
  $settings['redis.connection']['host'] = getenv('REDIS_HOSTNAME');
  $settings['redis.connection']['port'] = getenv('REDIS_PORT');
  $settings['cache']['default'] = 'cache.backend.redis';
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/redis.services.yml';

  // Manually add the classloader path, this is required for the container cache bin definition below
  // and allows to use it without the redis module being enabled.
  $class_loader->addPsr4('Drupal\\redis\\', $app_root . '/' . $site_path . '/modules/contrib/redis/src');

  // Use redis for container cache.
  // The container cache is used to load the container definition itself, and
  // thus any configuration stored in the container itself is not available
  // yet. These lines force the container cache to use Redis rather than the
  // default SQL cache.
  $settings['bootstrap_container_definition'] = [
    'parameters' => [],
    'services' => [
      'redis.factory' => [
        'class' => 'Drupal\redis\ClientFactory',
      ],
      'cache.backend.redis' => [
        'class' => 'Drupal\redis\Cache\CacheBackendFactory',
        'arguments' => [
          '@redis.factory',
          '@cache_tags_provider.container',
          '@serialization.phpserialize'
        ],
      ],
      'cache.container' => [
        'class' => '\Drupal\redis\Cache\PhpRedis',
        'factory' => ['@cache.backend.redis', 'get'],
        'arguments' => ['container'],
      ],
      'cache_tags_provider.container' => [
        'class' => 'Drupal\redis\Cache\RedisCacheTagsChecksum',
        'arguments' => ['@redis.factory'],
      ],
      'serialization.phpserialize' => [
        'class' => 'Drupal\Component\Serialization\PhpSerialize',
      ],
    ],
  ];
}
