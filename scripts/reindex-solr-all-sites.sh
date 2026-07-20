#!/usr/bin/env bash

# Clear and fully rebuild every enabled Solr Search API index on every Unity site.
# Intended to be run from an Upsun application container, for example:
#   bash /app/scripts/reindex-solr-all-sites.sh
# Throttling defaults can be overridden with environment variables, for example:
#   INDEX_CHUNK_SIZE=250 CHUNK_PAUSE_SECONDS=10 bash /app/scripts/reindex-solr-all-sites.sh

set -uo pipefail

APP_ROOT="${PLATFORM_APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
SITES_ROOT="${APP_ROOT}/project/sites"
INDEX_CHUNK_SIZE="${INDEX_CHUNK_SIZE:-500}"
INDEX_BATCH_SIZE="${INDEX_BATCH_SIZE:-25}"
CHUNK_PAUSE_SECONDS="${CHUNK_PAUSE_SECONDS:-5}"
INDEX_PAUSE_SECONDS="${INDEX_PAUSE_SECONDS:-10}"
SITE_PAUSE_SECONDS="${SITE_PAUSE_SECONDS:-20}"
SOLR_READY_RETRIES="${SOLR_READY_RETRIES:-12}"
SOLR_READY_DELAY_SECONDS="${SOLR_READY_DELAY_SECONDS:-10}"
CLEAR_RETRIES="${CLEAR_RETRIES:-3}"

if command -v drush >/dev/null 2>&1; then
  DRUSH=(drush)
elif [[ -x "${APP_ROOT}/vendor/bin/drush" ]]; then
  DRUSH=("${APP_ROOT}/vendor/bin/drush")
else
  echo "ERROR: Drush was not found." >&2
  exit 1
fi

if [[ ! -d "${SITES_ROOT}" ]]; then
  echo "ERROR: Site directory not found: ${SITES_ROOT}" >&2
  exit 1
fi

mapfile -t SITES < <(
  find "${SITES_ROOT}" -mindepth 2 -maxdepth 2 -type f -name settings.php \
    -printf '%h\n' | sed 's#.*/##' | sort
)

if (( ${#SITES[@]} == 0 )); then
  echo "ERROR: No Unity sites were found under ${SITES_ROOT}." >&2
  exit 1
fi

for setting in \
  INDEX_CHUNK_SIZE INDEX_BATCH_SIZE CHUNK_PAUSE_SECONDS INDEX_PAUSE_SECONDS \
  SITE_PAUSE_SECONDS SOLR_READY_RETRIES SOLR_READY_DELAY_SECONDS CLEAR_RETRIES; do
  if [[ ! "${!setting}" =~ ^[0-9]+$ ]]; then
    echo "ERROR: ${setting} must be a non-negative integer." >&2
    exit 1
  fi
done

if (( INDEX_CHUNK_SIZE == 0 || INDEX_BATCH_SIZE == 0 || SOLR_READY_RETRIES == 0 || CLEAR_RETRIES == 0 )); then
  echo "ERROR: Chunk sizes and retry counts must be greater than zero." >&2
  exit 1
fi

pause_for() {
  local seconds="$1"
  if (( seconds > 0 )); then
    sleep "${seconds}"
  fi
}

wait_for_solr_index() {
  local site="$1"
  local index="$2"
  local attempt

  for (( attempt = 1; attempt <= SOLR_READY_RETRIES; attempt++ )); do
    if REINDEX_INDEX_ID="${index}" "${DRUSH[@]}" \
      --root="${APP_ROOT}/web" --uri="${site}" php:eval '
        $index = \Drupal::entityTypeManager()
          ->getStorage("search_api_index")
          ->load(getenv("REINDEX_INDEX_ID"));
        if (!$index || !$index->getServerInstance()->isAvailable()) {
          throw new \RuntimeException("Solr core is not ready.");
        }
      ' >/dev/null 2>&1; then
      return 0
    fi

    if (( attempt < SOLR_READY_RETRIES )); then
      echo "${site}/${index}: Solr core is not ready (attempt ${attempt}/${SOLR_READY_RETRIES}); waiting ${SOLR_READY_DELAY_SECONDS}s."
      pause_for "${SOLR_READY_DELAY_SECONDS}"
    fi
  done

  return 1
}

clear_solr_index() {
  local site="$1"
  local index="$2"
  local attempt
  local clear_output
  local clear_succeeded

  for (( attempt = 1; attempt <= CLEAR_RETRIES; attempt++ )); do
    if ! wait_for_solr_index "${site}" "${index}"; then
      return 1
    fi

    clear_succeeded=true
    if ! clear_output=$("${DRUSH[@]}" --root="${APP_ROOT}/web" --uri="${site}" \
      --yes search-api:clear "${index}" 2>&1); then
      clear_succeeded=false
    fi
    printf '%s\n' "${clear_output}"

    # Search API can log a backend exception followed by a success message and
    # still return zero, so inspect the output as well as the exit status.
    if [[ "${clear_succeeded}" == true ]] && ! grep -qE '\[error\]|SearchApiSolrException|SolrCore is loading' <<< "${clear_output}"; then
      return 0
    fi

    if (( attempt < CLEAR_RETRIES )); then
      echo "${site}/${index}: clear attempt ${attempt}/${CLEAR_RETRIES} failed; waiting ${SOLR_READY_DELAY_SECONDS}s before retrying." >&2
      pause_for "${SOLR_READY_DELAY_SECONDS}"
    fi
  done

  return 1
}

get_remaining_items() {
  local site="$1"
  local index="$2"
  local remaining

  if ! remaining=$(REINDEX_INDEX_ID="${index}" "${DRUSH[@]}" \
    --root="${APP_ROOT}/web" --uri="${site}" php:eval '
      $index = \Drupal::entityTypeManager()
        ->getStorage("search_api_index")
        ->load(getenv("REINDEX_INDEX_ID"));
      if (!$index) {
        throw new \RuntimeException("Search API index was not found.");
      }
      echo $index->getTrackerInstance()->getRemainingItemsCount();
    '); then
    return 1
  fi

  if [[ ! "${remaining}" =~ ^[0-9]+$ ]]; then
    echo "ERROR: ${site}/${index}: unexpected remaining-item count: ${remaining}" >&2
    return 1
  fi

  printf '%s\n' "${remaining}"
}

rebuild_solr_index() {
  local site="$1"
  local index="$2"
  local before
  local after
  local stalled_attempts=0

  while true; do
    if ! before=$(get_remaining_items "${site}" "${index}"); then
      return 1
    fi

    if (( before == 0 )); then
      return 0
    fi

    if ! wait_for_solr_index "${site}" "${index}"; then
      return 1
    fi

    echo "${site}/${index}: ${before} items remaining; processing up to ${INDEX_CHUNK_SIZE}."
    if ! "${DRUSH[@]}" --root="${APP_ROOT}/web" --uri="${site}" \
      search-api:index --limit="${INDEX_CHUNK_SIZE}" --batch-size="${INDEX_BATCH_SIZE}" "${index}"; then
      return 1
    fi

    if ! after=$(get_remaining_items "${site}" "${index}"); then
      return 1
    fi

    if (( after >= before )); then
      (( stalled_attempts++ ))
      if (( stalled_attempts >= 3 )); then
        echo "ERROR: ${site}/${index}: indexing made no progress after ${stalled_attempts} attempts." >&2
        return 1
      fi
    else
      stalled_attempts=0
    fi

    if (( after > 0 )); then
      echo "${site}/${index}: pausing ${CHUNK_PAUSE_SECONDS}s before the next chunk."
      pause_for "${CHUNK_PAUSE_SECONDS}"
    fi
  done
}

failed_sites=()
successful_sites=()
skipped_sites=()
started_at=$(date +%s)

echo "Rebuilding all enabled Solr Search API indexes across ${#SITES[@]} sites."
echo "Chunk size: ${INDEX_CHUNK_SIZE}; batch size: ${INDEX_BATCH_SIZE}."
echo "Search results may be incomplete until each site's indexing finishes."

# Process one site at a time to avoid waking every Solr core at once.
for site in "${SITES[@]}"; do
  echo
  echo "===== ${site}: rebuilding Drupal caches ====="

  if ! "${DRUSH[@]}" --root="${APP_ROOT}/web" --uri="${site}" --yes cache:rebuild; then
    echo "ERROR: ${site}: cache rebuild failed; Solr reindex skipped." >&2
    failed_sites+=("${site} (cache rebuild)")
    continue
  fi

  echo "===== ${site}: discovering Solr indexes ====="

  if ! index_output=$("${DRUSH[@]}" --root="${APP_ROOT}/web" --uri="${site}" php:eval '
    foreach (\Drupal::entityTypeManager()->getStorage("search_api_index")->loadMultiple() as $index) {
      $server = $index->getServerInstanceIfAvailable();
      if ($index->status() && $server && $server->getBackendId() === "search_api_solr") {
        echo $index->id(), PHP_EOL;
      }
    }
  '); then
    echo "ERROR: ${site}: could not discover Solr indexes." >&2
    failed_sites+=("${site} (discovery)")
    continue
  fi

  solr_indexes=()
  if [[ -n "${index_output}" ]]; then
    mapfile -t solr_indexes <<< "${index_output}"
  fi

  if (( ${#solr_indexes[@]} == 0 )); then
    echo "No enabled Solr indexes found; skipping ${site}."
    skipped_sites+=("${site}")
    continue
  fi

  site_failed=false
  for index in "${solr_indexes[@]}"; do
    echo "===== ${site}/${index}: clearing ====="

    if ! clear_solr_index "${site}" "${index}"; then
      echo "ERROR: ${site}/${index}: clear failed; reindex skipped." >&2
      failed_sites+=("${site}/${index} (clear)")
      site_failed=true
      pause_for "${INDEX_PAUSE_SECONDS}"
      continue
    fi

    echo "===== ${site}/${index}: rebuilding ====="

    if ! rebuild_solr_index "${site}" "${index}"; then
      echo "ERROR: ${site}/${index}: reindex failed." >&2
      failed_sites+=("${site}/${index} (index)")
      site_failed=true
    fi

    pause_for "${INDEX_PAUSE_SECONDS}"
  done

  if [[ "${site_failed}" == true ]]; then
    pause_for "${SITE_PAUSE_SECONDS}"
    continue
  fi

  successful_sites+=("${site}")
  echo "===== ${site}: complete ====="
  pause_for "${SITE_PAUSE_SECONDS}"
done

elapsed=$(( $(date +%s) - started_at ))

echo
echo "Finished in ${elapsed} seconds: ${#successful_sites[@]} sites rebuilt, ${#skipped_sites[@]} without enabled Solr indexes skipped, ${#failed_sites[@]} failures."

if (( ${#failed_sites[@]} > 0 )); then
  printf 'Failed: %s\n' "${failed_sites[*]}" >&2
  exit 1
fi
