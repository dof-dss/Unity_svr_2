<?php

/**
 * @file
 * Uploads the fleet report and per-site reports to Google Cloud Storage.
 *
 * Usage: php upload-to-gcs.php <fleet-report.json> <per-site-json-dir>
 *
 * Required environment variables:
 *   GCS_BUCKET                           Target bucket name.
 *   GOOGLE_APPLICATION_CREDENTIALS_JSON   Full service account key JSON,
 *                                         as a single string. Set this as a
 *                                         *sensitive* Upsun variable, never
 *                                         commit it to the repo.
 *
 * Optional:
 *   GCS_PREFIX   Path prefix inside the bucket. Defaults to "drupal-status".
 *
 * Requires: composer require google/cloud-storage
 */

require __DIR__ . '/../../vendor/autoload.php';

use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;

if ($argc < 3) {
  fwrite(STDERR, "Usage: php upload-to-gcs.php <fleet-report.json> <per-site-json-dir>\n");
  exit(1);
}

[, $fleet_report_path, $site_dir] = $argv;

$bucket_name = getenv('GCS_BUCKET') ?: null;
$prefix = trim((string) (getenv('GCS_PREFIX') ?: 'drupal-status'), '/');
$credentials_json = getenv('GOOGLE_APPLICATION_CREDENTIALS_JSON') ?: null;

if (!$bucket_name) {
  fwrite(STDERR, "ERROR: GCS_BUCKET is not set.\n");
  exit(1);
}
if (!$credentials_json) {
  fwrite(STDERR, "ERROR: GOOGLE_APPLICATION_CREDENTIALS_JSON is not set.\n");
  exit(1);
}
if (!is_file($fleet_report_path)) {
  fwrite(STDERR, "ERROR: fleet report not found at $fleet_report_path\n");
  exit(1);
}

$key_file = json_decode($credentials_json, true);
if (!is_array($key_file)) {
  fwrite(STDERR, "ERROR: GOOGLE_APPLICATION_CREDENTIALS_JSON is not valid JSON.\n");
  exit(1);
}

$storage = new StorageClient(['keyFile' => $key_file]);
$bucket = $storage->bucket($bucket_name);

$environment = getenv('PLATFORM_ENVIRONMENT') ?: 'unknown';

/**
 * Uploads a single local file to the given object path in the bucket.
 */
function dashboard_upload_file(Bucket $bucket, string $local_path, string $object_name): void {
  $handle = fopen($local_path, 'r');
  if ($handle === false) {
    fwrite(STDERR, "WARNING: could not open $local_path, skipping.\n");
    return;
  }
  $bucket->upload($handle, [
    'name' => $object_name,
    'metadata' => ['contentType' => 'application/json'],
  ]);
  fclose($handle);
  echo "Uploaded: $object_name\n";
}

// 1. Timestamped snapshot, for history/trend views.
$timestamp = date('Y-m-d\TH-i-s');
dashboard_upload_file($bucket, $fleet_report_path, "$prefix/$environment/history/$timestamp.json");

// 2. Rolling "latest" object -- what the dashboard reads by default.
dashboard_upload_file($bucket, $fleet_report_path, "$prefix/$environment/latest.json");

// 3. Per-site files, so the dashboard can drill into one site without
// downloading the whole fleet report.
foreach (glob("$site_dir/*.json") ?: [] as $file) {
  $base = basename($file);
  if ($base === 'fleet-report.json') {
    continue;
  }
  dashboard_upload_file($bucket, $file, "$prefix/$environment/sites/$base");
}
