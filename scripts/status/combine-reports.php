<?php

/**
 * @file
 * Combines per-site status JSON files into one fleet-level report.
 *
 * Usage: php combine-reports.php <dir-containing-site-json-files>
 *
 * Reads Upsun's built-in PLATFORM_* environment variables to stamp the
 * report with which project/environment it came from.
 */

if ($argc < 2) {
  fwrite(STDERR, "Usage: php combine-reports.php <dir>\n");
  exit(1);
}

$dir = rtrim($argv[1], '/');
$files = glob("$dir/*.json") ?: [];

$sites = [];
foreach ($files as $file) {
  if (basename($file) === 'fleet-report.json') {
    continue;
  }
  $contents = file_get_contents($file);
  $data = $contents ? json_decode($contents, true) : null;
  if (is_array($data) && isset($data['site'])) {
    $sites[] = $data;
  }
  else {
    fwrite(STDERR, "Skipping unreadable/invalid report: $file\n");
  }
}

usort($sites, fn(array $a, array $b): int => strcmp($a['site'] ?? '', $b['site'] ?? ''));

$fleet = [
  'generated_at' => date('c'),
  'project' => getenv('PLATFORM_PROJECT') ?: null,
  'environment' => getenv('PLATFORM_ENVIRONMENT') ?: null,
  'site_count' => count($sites),
  'security_updates_total' => array_sum(array_map(fn(array $s): int => $s['summary']['security_updates'] ?? 0, $sites)),
  'updates_available_total' => array_sum(array_map(fn(array $s): int => $s['summary']['updates_available'] ?? 0, $sites)),
  'sites' => $sites,
];

echo json_encode($fleet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
