#!/usr/bin/env bash
#
# Discovers every Drupal multisite directory, runs report.php against each
# one via Drush to collect that site's "Errors found" / "Warnings found"
# issues from admin/reports/status, combines the results into a single
# fleet report, and uploads everything to Google Cloud Storage.
#
# Why every site and not just one: status report requirements are per-site
# (cron last-run time, file system permissions, trusted host settings,
# database connectivity, etc. can all differ between multisite instances
# even though they share one codebase) -- unlike a module-version check,
# checking a single representative site here would silently miss issues
# specific to every other site.
#
# Environment variables:
#   WEBROOT      Path to the Drupal webroot (contains sites/).
#                Defaults to two levels up from this script + /web.
#   DRUSH_BIN    Path to the drush binary. Defaults to whatever is on
#                PATH, falling back to vendor/bin/drush.
#   OUTPUT_DIR   Where per-site JSON is written before upload. Defaults to
#                /tmp/status-report.
#
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WEBROOT="${WEBROOT:-$SCRIPT_DIR/../../web}"
OUTPUT_DIR="${OUTPUT_DIR:-/tmp/status-report}"
DRUSH_BIN="${DRUSH_BIN:-$(command -v drush || echo "$SCRIPT_DIR/../../vendor/bin/drush")}"

if [[ ! -x "$DRUSH_BIN" ]] && ! command -v "$DRUSH_BIN" >/dev/null 2>&1; then
  echo "ERROR: could not find a drush binary (looked for \$DRUSH_BIN=$DRUSH_BIN)." >&2
  exit 1
fi

if [[ ! -d "$WEBROOT/sites" ]]; then
  echo "ERROR: no sites/ directory found under WEBROOT=$WEBROOT. Set WEBROOT explicitly." >&2
  exit 1
fi

mkdir -p "$OUTPUT_DIR"
rm -f "$OUTPUT_DIR"/*.json "$OUTPUT_DIR"/*.err

cd "$WEBROOT"

# Discover multisite directories: any sites/<name>/ folder containing a
# settings.php, excluding the shared "all" directory.
SITES=()
for dir in sites/*/; do
  site="$(basename "$dir")"
  if [[ "$site" != "all" && -f "${dir}settings.php" ]]; then
    SITES+=("$site")
  fi
done

if [[ ${#SITES[@]} -eq 0 ]]; then
  echo "ERROR: no multisite directories found under $WEBROOT/sites." >&2
  exit 1
fi

echo "Found ${#SITES[@]} site(s): ${SITES[*]}"

FAILED=0
for site in "${SITES[@]}"; do
  echo "Checking $site..."
  if DASHBOARD_SITE_ID="$site" \
      "$DRUSH_BIN" --uri="$site" scr "$SCRIPT_DIR/report.php" \
      > "$OUTPUT_DIR/${site}.json" 2> "$OUTPUT_DIR/${site}.err"; then
    echo "  OK"
  else
    echo "  FAILED - see below"
    cat "$OUTPUT_DIR/${site}.err" >&2
    FAILED=1
  fi
done

echo "Combining per-site reports..."
php "$SCRIPT_DIR/combine-reports.php" "$OUTPUT_DIR" > "$OUTPUT_DIR/fleet-report.json"

echo "Uploading to Google Cloud Storage..."
php "$SCRIPT_DIR/upload-to-gcs.php" "$OUTPUT_DIR/fleet-report.json" "$OUTPUT_DIR"

if [[ "$FAILED" -eq 1 ]]; then
  echo "One or more sites failed to report status (see logs above)." >&2
  exit 1
fi

echo "Done."
