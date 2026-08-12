<?php

// @codingStandardsIgnoreFile

// Extract the directory name for multi site identification.
$subsite_id = basename(__DIR__);

include $app_root . '/sites/site.settings.php';

$settings['hash_salt'] = getenv('HASH_SALT');

if (getenv('IS_DDEV_PROJECT')) {
  $databases['default']['default']['database'] = $subsite_id;
}
