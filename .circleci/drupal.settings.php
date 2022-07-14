<?php

$databases['default']['default'] = [
  'database'  => $subsite_id,
  'username'  => getenv('DB_USER'),
  'password'  => getenv('DB_PASS'),
  'prefix'    => getenv('DB_PREFIX'),
  'host'      => getenv('DB_HOST'),
  'port'      => getenv('DB_PORT'),
  'namespace' => 'Drupal\Core\Database\Driver\mysql',
  'driver'    => getenv('DB_DRIVER'),
];
