# Solr 9 architecture

This repository uses one Solr 9.9 service with one core and one committed
configset per site. Unity 2 currently declares 14 Solr-hosted sites in
`project/project.yml`; 13 have a committed Drupal `solr_default` server.

The `ircommission` hosting declaration does not currently have a committed Drupal `solr_default` server. Its existing configset is preserved, but the generator intentionally skips it until that application configuration exists.

## Ownership boundary

The split is intentional:

- `maestro-hosting` owns the common infrastructure mechanics: the Solr image,
  authentication, optional modules, persistent data volume, DDEV startup
  script, compose templates, and Platform.sh service generation.
- This repository owns behaviour that can differ by site: the site inventory,
  Drupal Search API server configuration, local connector patch, and generated
  configset under `.platform/solr_configsets/<site>/conf`.

Keeping configsets here makes schema changes reviewable beside the Drupal
configuration that produced them. It also prevents a shared generic configset
from silently coupling sites with different fields, languages, processors, or
Search API Solr module versions. Maestro remains useful as a distribution and
generation layer, but is not the source of truth for site schemas.

## Runtime mapping

For a site called `<site>`:

| Concern | Value |
| --- | --- |
| Platform/DDEV core | `<site>_index` |
| Configset | `.platform/solr_configsets/<site>/conf` |
| Drupal server | `project/config/<site>/config/search_api.server.solr_default.yml` |
| Local override | `project/config/<site>/local/config_split.patch.search_api.server.solr_default.yml` |

Platform archives each site's configset into its own core. DDEV mounts the
single repository configset root read-only, then the common startup script
creates missing cores and refreshes the configuration of persistent cores
before Solr starts. Index data remains in the DDEV Solr volume and is not
committed.

Local connector patches use the unique `ddev-<project-name>-solr` container
hostname. Do not shorten this to `solr`: when several DDEV projects are running,
that shared network alias can resolve to a different codebase's container.

## Maintaining configsets

Start DDEV with current databases, then run:

```bash
scripts/solr/configure-local-connectors --all
scripts/solr/generate-configsets --all
scripts/solr/verify-configsets
```

The verifier checks provisioned infrastructure only: every declared Solr site
must have a `<site>_index` core, a repository-owned configset, and matching
Platform and DDEV declarations. It does not inspect the site's active Search
API backend or local connector patch.

The connector command clears stale Drupal plugin caches, updates only the
active `solr_default` connector for sites with enabled Solr-backed indexes,
and asks Drupal to verify that each core is available. Maestro-generated DDEV
database-pull providers run it automatically after importing hosted databases.

The generator asks each multisite Drupal installation to export its active
`solr_default` server configuration specifically for Solr 9.9. It validates the
archive before replacing the committed site configset. A single site can be
updated with `scripts/solr/generate-configsets <site>`.

Regenerate a configset whenever Search API fields/processors, languages,
Search API Solr, or the target Solr version changes. Review the resulting XML
and text-file diff with the associated Drupal configuration change.

## Validation and rollout

Run static verification in every PR. On a disposable or backed-up environment,
start Solr, check the Search API server status for every configured site,
reindex, and exercise representative search, filtering, sorting, autocomplete,
attachments, and multilingual behaviour where applicable. Promote one
codebase at a time through its edge QA round.

Changing a configset does not transform existing index data. A full reindex is
therefore part of rollout, and rollback must restore both compatible code/config
and a compatible index (or reindex again). Do not automatically delete a
persistent local or hosted core during configuration deployment.

## Generated files and upgrades

The DDEV compose files, startup script, and `.platform/services.yaml` are
generated from `maestro-hosting`. After changing the shared package, release it
first, update this repository to that release, regenerate the project files,
and run the verifier. Avoid editing the generated runtime independently unless
the matching Maestro template change is already in progress, because a later
project build will overwrite local divergence.
