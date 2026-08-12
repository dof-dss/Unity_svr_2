#!/usr/bin/env bash
#
# Runs the Drupal core + contrib update check ONCE, against a single
# representative multisite instance, and uploads the result to Google
# Cloud Storage.
#
# Why once and not per-site: Drupal multisite shares one codebase, so
# every site has exactly the same installed core/contrib projects and
# versions on disk. Update Manager reports on what's present in the
# codebase, not on what's enabled per site, so checking every site
# individually just repeats the same updates.drupal.org fetches N times
# for identical data. This checks one site (enough to bootstrap Drupal)
# and records every site the codebase actually serves alongside the
# result, for context.
#
# Environment variables:
#   WEBROOT               Path to the Drupal webroot (contains sites/).
#                          Defaults to two levels up from this script + /web.
#   PRIMARY_SITE           Which multisite directory to run the check
#                          through. Defaults to the first one found,
#                          alphabetically. Set this explicitly for a
#                          stable, predictable choice (recommended).
#   DRUSH_BIN              Path to the drush binary. Defaults to whatever is
#                          on PATH, falling back to vendor/bin/drush.
#   OUTPUT_DIR             Where JSON is written before upload.
#                          Defaults to /tmp/status-report.
#   REFRESH_UPDATE_DATA    Set to "1" to force a live check against
#                          updates.drupal.org (slower). Recommended only on
#                          the daily cron run, not on every deploy.
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
rm -f "$OUTPUT_DIR"/*.json "$OUTPUT_DIR"/*.err "$OUTPUT_DIR"/sites.txt

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

printf '%s\n' "${SITES[@]}" | sort > "$OUTPUT_DIR/sites.txt"
echo "Found ${#SITES[@]} site(s): ${SITES[*]}"

DEFAULT_SITE="$(sort "$OUTPUT_DIR/sites.txt" | head -n1)"
TARGET_SITE="${PRIMARY_SITE:-$DEFAULT_SITE}"

if ! printf '%s\n' "${SITES[@]}" | grep -qx "$TARGET_SITE"; then
  echo "ERROR: PRIMARY_SITE='$TARGET_SITE' is not among the discovered sites: ${SITES[*]}" >&2
  exit 1
fi

echo "Checking update status via '$TARGET_SITE' (codebase is shared across all ${#SITES[@]} site(s))..."

if ! DASHBOARD_SITE_ID="$TARGET_SITE" REFRESH_UPDATE_DATA="${REFRESH_UPDATE_DATA:-0}" \
    "$DRUSH_BIN" --uri="$TARGET_SITE" scr "$SCRIPT_DIR/report.php" \
    > "$OUTPUT_DIR/report.json" 2> "$OUTPUT_DIR/report.err"; then
  echo "FAILED - see below" >&2
  cat "$OUTPUT_DIR/report.err" >&2
  exit 1
fi
cat "$OUTPUT_DIR/report.err" >&2 || true

echo "Finalizing report..."
php "$SCRIPT_DIR/combine-reports.php" "$OUTPUT_DIR/report.json" "$OUTPUT_DIR/sites.txt" > "$OUTPUT_DIR/final-report.json"

echo "Uploading to Google Cloud Storage..."
php "$SCRIPT_DIR/upload-to-gcs.php" "$OUTPUT_DIR/final-report.json"

echo "Done."
