#!/usr/bin/env bash
#ddev-generated
set -euo pipefail

security_source="/mnt/ddev_config/solr/security.json"
if [[ ! -f "$security_source" ]]; then
  echo "Missing Solr security policy: ${security_source}" >&2
  exit 1
fi
install -m 600 "$security_source" /var/solr/data/security.json

if [[ -z "${SOLR_SITES:-}" ]]; then
  echo "No Solr-enabled sites are configured."
  exec solr -f
fi

for site in ${SOLR_SITES}; do
  if [[ ! "$site" =~ ^[a-z0-9_]+$ ]]; then
    echo "Invalid Solr site identifier: $site" >&2
    exit 1
  fi

  core="${site}_index"
  configset="/solr-configsets/${site}"
  source_conf="${configset}/conf"
  target_conf="/var/solr/data/${core}/conf"

  if [[ ! -f "${source_conf}/solrconfig.xml" || ! -f "${source_conf}/schema.xml" ]]; then
    echo "Missing configset for ${site}: expected schema.xml and solrconfig.xml in ${source_conf}" >&2
    exit 1
  fi

  precreate-core "$core" "$configset"

  # precreate-core does not refresh an existing persistent core. Replace its
  # configuration with the committed configset before Solr starts.
  if [[ -d "$target_conf" ]]; then
    find "$target_conf" -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +
    cp -a "${source_conf}/." "$target_conf/"
  fi
done

exec solr -f
