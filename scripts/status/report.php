<?php

/**
 * @file
 * Collects the "Errors found" and "Warnings found" issues from Drupal's
 * Status report page (admin/reports/status) for ONE site.
 *
 * Unlike core/contrib module versions, these come from hook_requirements()
 * ('runtime' phase) and are genuinely per-site -- cron last-run time, file
 * system permissions, trusted host settings, database connectivity, and so
 * on can all differ between multisite instances even though they share one
 * codebase. So, unlike a module-version check, this has to run against
 * every site individually rather than once per codebase.
 *
 * Run via Drush against a specific multisite instance:
 *
 *   drush --uri=<site-dir-or-uri> scr scripts/status/report.php
 *
 * Environment variables read:
 *   DASHBOARD_SITE_ID   Identifier to stamp on the report (usually the
 *                        multisite directory name). Defaults to "unknown".
 *
 * Outputs a JSON report to stdout. Warnings/errors from the script itself
 * go to stderr so they don't corrupt the JSON.
 */

$site_id = getenv('DASHBOARD_SITE_ID') ?: 'unknown';

// Same service, same 'runtime' phase, as admin/reports/status itself --
// see \Drupal\system\Element\StatusReportPage::preRenderCounters(), which
// buckets requirements into "Errors"/"Warnings"/"Checked" counts using
// exactly the severity value read below.
$requirements = \Drupal::service('system.manager')->listRequirements();

// SystemManager::listRequirements() include_once's core/includes/install.inc
// as part of doing its job, and that's what defines these -- so they're
// available by now. Defined defensively anyway, matching how this script
// already treats update.module's UPDATE_* constants, in case a future core
// refactor ever changes that.
if (!defined('REQUIREMENT_ERROR')) {
  define('REQUIREMENT_INFO', -1);
  define('REQUIREMENT_OK', 0);
  define('REQUIREMENT_WARNING', 1);
  define('REQUIREMENT_ERROR', 2);
}

/**
 * Reduces a requirement's 'title'/'value'/'description' to a plain string.
 *
 * These come back as plain strings, TranslatableMarkup objects, or full
 * render arrays depending on the module that raised them (e.g. cron's
 * description is a render array containing a "Run cron" link) -- render
 * arrays are rendered the same way the status report page itself renders
 * them; everything else just needs stringifying.
 */
function dashboard_render_requirement_text($value): ?string {
  if ($value === null || $value === '') {
    return null;
  }
  if (is_array($value)) {
    return trim((string) \Drupal::service('renderer')->renderInIsolation($value));
  }
  return trim((string) $value);
}

$severity_labels = [
  REQUIREMENT_ERROR => 'error',
  REQUIREMENT_WARNING => 'warning',
];

$issues = [];
foreach ($requirements as $key => $requirement) {
  $severity = $requirement['severity'] ?? REQUIREMENT_INFO;
  if (!isset($severity_labels[$severity])) {
    // REQUIREMENT_OK, REQUIREMENT_INFO, or no severity at all -- only
    // "Errors found" and "Warnings found" are in scope here.
    continue;
  }

  $issues[] = [
    'key' => $key,
    'severity' => $severity_labels[$severity],
    'title' => dashboard_render_requirement_text($requirement['title'] ?? $key),
    'value' => dashboard_render_requirement_text($requirement['value'] ?? null),
    'description' => dashboard_render_requirement_text($requirement['description'] ?? null),
  ];
}

// Errors before warnings (matches the section order on admin/reports/status
// itself), alphabetical by title within each group.
usort($issues, function (array $a, array $b): int {
  if ($a['severity'] !== $b['severity']) {
    return $a['severity'] === 'error' ? -1 : 1;
  }
  return strcasecmp((string) $a['title'], (string) $b['title']);
});

$errors_found = count(array_filter($issues, fn(array $i): bool => $i['severity'] === 'error'));
$warnings_found = count(array_filter($issues, fn(array $i): bool => $i['severity'] === 'warning'));

$report = [
  'site' => $site_id,
  'timestamp' => date('c'),
  'drupal_core_version' => \Drupal::VERSION,
  'php_version' => PHP_VERSION,
  'issues' => $issues,
  'summary' => [
    'errors_found' => $errors_found,
    'warnings_found' => $warnings_found,
  ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
