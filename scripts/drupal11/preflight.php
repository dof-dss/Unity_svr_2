<?php

/**
 * @file
 * Repairs logging and ordinary Views before Drupal 11 database updates.
 *
 * Run with drush --uri=<site> upgrade-preflight.
 * Managed by Maestro Hosting Common resources for Unity and Corp Lite.
 */

// Logging must work before configuration saves can emit log messages.
$schema = \Drupal::database()->schema();
if (\Drupal::config('core.extension')->get('module.dblog') !== NULL && !$schema->tableExists('watchdog')) {
  require_once \Drupal::root() . '/core/modules/dblog/dblog.install';
  $schema->createTable('watchdog', dblog_schema()['watchdog']);
  print "Created the missing watchdog table using Drupal core schema.\n";
}

// Use raw configuration to avoid loading invalid Views plugins.
$config_factory = \Drupal::configFactory();
$repaired = 0;
foreach ($config_factory->listAll('views.view.') as $name) {
  $view = $config_factory->getEditable($name);
  if (str_starts_with($view->get('base_table') ?? '', 'search_api_index_')) {
    continue;
  }
  $displays = $view->get('display') ?? [];
  $changed = FALSE;
  foreach ($displays as &$display) {
    if (($display['display_options']['cache']['type'] ?? NULL) === 'search_api_none') {
      $display['display_options']['cache']['type'] = 'none';
      $changed = TRUE;
    }
  }
  unset($display);
  if ($changed) {
    $view->set('display', $displays)->save(TRUE);
    $repaired++;
  }
}
print "Repaired cache settings on $repaired ordinary Views.\n";
