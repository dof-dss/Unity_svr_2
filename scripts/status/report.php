<?php

/**
 * @file
 * Collects Drupal core + contrib module/theme update status for ONE site.
 *
 * Run via Drush against a specific multisite instance:
 *
 *   drush --uri=<site-dir-or-uri> scr scripts/status/report.php
 *
 * Environment variables read:
 *   DASHBOARD_SITE_ID    Identifier to stamp on the report (usually the
 *                         multisite directory name). Defaults to "unknown".
 *   REFRESH_UPDATE_DATA  Set to "1" to force a live fetch against
 *                         updates.drupal.org instead of using Drupal's
 *                         cached update data. Use sparingly (slow, and
 *                         updates.drupal.org rate-limits aggressive polling).
 *
 * Requires the core "update" module to be enabled on the site being
 * checked -- this is Drupal's standard Update Manager backend and is what
 * powers the "Available updates" report / email notifications. If it's
 * disabled, enable it with:
 *
 *   drush --uri=<site> pm:enable update -y
 *
 * Outputs a JSON report to stdout. Warnings/errors go to stderr so they
 * don't corrupt the JSON.
 */

$refresh = getenv('REFRESH_UPDATE_DATA') === '1';
$site_id = getenv('DASHBOARD_SITE_ID') ?: 'unknown';

if (!\Drupal::moduleHandler()->moduleExists('update')) {
  fwrite(STDERR, "ERROR: the 'update' module is not enabled on site '$site_id'. Enable it with: drush --uri=$site_id pm:enable update -y\n");
  exit(1);
}

// update.module itself defines the UPDATE_* status constants used below
// (as top-level define() calls, not inside a function) -- loadInclude()'s
// $type/$name split lets us force-load that file explicitly rather than
// assuming it was already pulled in by whatever bootstrapped this script.
// require_once is a no-op if it was already loaded, so this is safe either
// way.
\Drupal::moduleHandler()->loadInclude('update', 'module');
\Drupal::moduleHandler()->loadInclude('update', 'inc', 'update.compare');
\Drupal::moduleHandler()->loadInclude('update', 'inc', 'update.fetch');

// Defensive fallback: these values have been stable across Drupal 8-11.
// If a future core refactor ever moves them somewhere loadInclude() above
// can't reach, define them ourselves rather than fatal-erroring.
if (!defined('UPDATE_NOT_SECURE')) {
  define('UPDATE_NOT_SECURE', 12);
  define('UPDATE_REVOKED', 13);
  define('UPDATE_NOT_SUPPORTED', 14);
  define('UPDATE_NOT_CURRENT', 7);
  define('UPDATE_CURRENT', 5);
  define('UPDATE_NOT_CHECKED', -1);
  define('UPDATE_UNKNOWN', -2);
  define('UPDATE_FETCH_PENDING', -3);
}

$available = update_get_available($refresh);

if (empty($available)) {
  fwrite(STDERR, "WARNING: no update data available for '$site_id'. If this is the first run, try REFRESH_UPDATE_DATA=1 so Drupal can fetch data from updates.drupal.org.\n");
}

$project_data = update_calculate_project_data($available);

// Map Drupal's UPDATE_* status constants to stable string values, so the
// downstream dashboard doesn't need to know Drupal's internals.
$status_map = [
  UPDATE_NOT_SECURE => 'security-update',
  UPDATE_REVOKED => 'revoked',
  UPDATE_NOT_SUPPORTED => 'unsupported',
  UPDATE_NOT_CURRENT => 'update-available',
  UPDATE_CURRENT => 'current',
  UPDATE_NOT_CHECKED => 'unknown',
  UPDATE_UNKNOWN => 'unknown',
  UPDATE_FETCH_PENDING => 'pending',
];

// Rank used purely to sort the most urgent items to the top of the list.
$severity_rank = [
  'security-update' => 0,
  'revoked' => 1,
  'unsupported' => 2,
  'update-available' => 3,
  'pending' => 4,
  'unknown' => 5,
  'current' => 6,
];

$projects = [];
foreach ($project_data as $key => $project) {
  $status_code = $project['status'] ?? UPDATE_UNKNOWN;
  $status = $status_map[$status_code] ?? 'unknown';

  $advisories = [];
  if (!empty($project['security updates']) && is_array($project['security updates'])) {
    foreach ($project['security updates'] as $release) {
      $advisories[] = [
        'version' => $release['version'] ?? null,
        'url' => $release['release_link'] ?? null,
      ];
    }
  }

  $projects[] = [
    'name' => $project['name'] ?? $key,
    'title' => $project['title'] ?? ($project['info']['name'] ?? $key),
    'type' => $project['project_type'] ?? 'module',
    'existing_version' => $project['existing_version'] ?? null,
    'recommended_version' => $project['recommended'] ?? null,
    'latest_version' => $project['latest_version'] ?? null,
    'status' => $status,
    'status_code' => (int) $status_code,
    'security_advisories' => $advisories,
  ];
}

usort($projects, function (array $a, array $b) use ($severity_rank): int {
  $rank_a = $severity_rank[$a['status']] ?? 99;
  $rank_b = $severity_rank[$b['status']] ?? 99;
  if ($rank_a === $rank_b) {
    return strcmp($a['name'], $b['name']);
  }
  return $rank_a <=> $rank_b;
});

$security_count = count(array_filter($projects, fn(array $p): bool => $p['status'] === 'security-update'));
$updates_count = count(array_filter($projects, fn(array $p): bool => in_array($p['status'], ['update-available', 'unsupported', 'revoked'], true)));

$report = [
  'site' => $site_id,
  'timestamp' => date('c'),
  'drupal_core_version' => \Drupal::VERSION,
  'php_version' => PHP_VERSION,
  'projects' => $projects,
  'summary' => [
    'total_projects' => count($projects),
    'security_updates' => $security_count,
    'updates_available' => $updates_count,
  ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
