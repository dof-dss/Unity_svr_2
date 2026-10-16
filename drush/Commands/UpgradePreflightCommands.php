<?php

namespace Drush\Commands;

use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;

/**
 * Repairs prerequisites for the Drupal 11 upgrade in Unity and Corp Lite.
 *
 * Some existing databases have dblog enabled but no watchdog table, or ordinary
 * Views configured with the Search API cache plugin search_api_none. Logging
 * during updates/configuration saves can fail without watchdog, and Drupal 11
 * post-updates can load those Views before configuration import can repair them.
 *
 * The usual updatedb (updb) and config-import (cim) sequence cannot be relied on
 * to repair these prerequisites before encountering them. This command restores
 * the missing table using core's schema and repairs the affected ordinary Views
 * through raw configuration, avoiding instantiation of invalid Views plugins.
 * Search API Views and other cache plugin selections are preserved.
 *
 * Run with an explicit site URI before updb, then import configuration as usual.
 * This supplements those commands; it does not replace database updates or
 * configuration import. It requires a working Drupal bootstrap, changes active
 * database state, and is safe to repeat once the prerequisites are repaired.
 *
 * Managed by Maestro Hosting Common resources. Keep this class and its backing
 * scripts/drupal11/preflight.php in sync with those resources when regenerating
 * projects. The neutral command name allows Corp Lite to use it independently
 * of the Unity-specific modules.
 */
final class UpgradePreflightCommands extends DrushCommands {

  /**
   * Repairs logging and Views prerequisites before database updates.
   */
  #[CLI\Command(name: 'upgrade-preflight')]
  #[CLI\Bootstrap(level: DrupalBootLevels::FULL)]
  public function preflight(): void {
    require dirname(__DIR__, 2) . '/scripts/drupal11/preflight.php';
  }

}
