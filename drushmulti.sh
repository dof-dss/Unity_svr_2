#!/usr/bin/env bash

# Custom command that is designed to run a drush command on all sites in Unity.
# For example to run a cache clear across all sites:
#   ./drushmulti.sh cr  (you must be in the 'app' directory to do this)

# Check that a drush command has been supplied
if [ $# -eq 0 ]
  then
    echo "No drush command supplied, please supply a drush command to run on all sites e.g. './drushmulti.sh cr'"
    exit 0
fi

# Detect whether we are running locally or on Platform.sh
if [ -z "${PLATFORM_ENVIRONMENT}" ]; then
  PREFIX="lando"
else
  PREFIX=""
fi

# Run command across all sites
for site in `ls -l web/sites | grep ^d | awk '!/default/{print $9}'`
do
  echo "** $site **"
  ${PREFIX} drush -l $site $1 $2 $3 $4 $5
done


