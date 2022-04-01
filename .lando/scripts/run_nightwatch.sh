#!/bin/bash

. /helpers/log.sh

# Parse command options & flags.
for i in "$@"
do
case $i in
    -s=*|--site=*)
    site="${i#*=}"
    shift # past argument=value
    ;;
    -t=*|--test=*)
    test="${i#*=}"
    shift # past argument=value
    ;;
    *)
          # unknown option
    ;;
esac
done

lando_green "Running Nightwatch test for site: $site";

# Will need to add more sites in here as we go, code could
# possibly be improved to derive site URL from site name (as the
# database name is predictable) ?
if [ $site = "uregni" ]; then
export DRUPAL_TEST_BASE_URL=http://uregni.gov.uk.lndo.site
export DRUPAL_TEST_DB_URL=mysql://drupal8:drupal8@database/uregni
elif [ $site = "liofa" ]; then
export DRUPAL_TEST_BASE_URL=http://liofa.eu.lndo.site
export DRUPAL_TEST_DB_URL=mysql://drupal8:drupal8@database/liofa
elif [ $site = "fictcommission" ]; then
export DRUPAL_TEST_BASE_URL=http://fictcommission.org.lndo.site
export DRUPAL_TEST_DB_URL=mysql://drupal8:drupal8@database/fictcommission
else
lando_red "Please specify a valid Unity site in which to run the tests"
exit 1
fi

yarn --cwd=/app/web/core test:nightwatch $test
