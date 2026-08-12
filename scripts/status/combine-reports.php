<?php

/**
 * @file
 * Finalizes the single-site status report produced by report.php: adds
 * which multisite directories this codebase actually serves, plus
 * environment context, since the check itself only ran once against one
 * representative site.
 *
 * (Filename is a holdover from an earlier per-site-then-merge design;
 * kept as-is to avoid churn. What it does now is finalize a single
 * report, not combine several.)
 *
 * Usage: php combine-reports.php <report.json> <sites-list-file>
 *
 * <sites-list-file> is a plain text file, one multisite directory name
 * per line (collect-all-sites.sh writes this).
 */

if ($argc < 3) {
  fwrite(STDERR, "Usage: php combine-reports.php <report.json> <sites-list-file>\n");
  exit(1);
}

[, $report_path, $sites_list_path] = $argv;

$contents = is_file($report_path) ? file_get_contents($report_path) : false;
$report = $contents ? json_decode($contents, true) : null;

if (!is_array($report)) {
  fwrite(STDERR, "ERROR: could not read/parse $report_path\n");
  exit(1);
}

$sites_covered = [];
if (is_file($sites_list_path)) {
  $lines = file($sites_list_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
  $sites_covered = array_values(array_unique(array_map('trim', $lines)));
  sort($sites_covered);
}

$report['sites_covered'] = $sites_covered;
$report['project'] = getenv('PLATFORM_PROJECT') ?: null;
$report['environment'] = getenv('PLATFORM_ENVIRONMENT') ?: null;
$report['generated_at'] = date('c');

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
