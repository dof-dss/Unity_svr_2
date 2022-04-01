#!/bin/bash

. /helpers/log.sh

# Parse command options & flags.
for i in "$@"
do
case $i in
    -n=*|--name=*)
    database="${i#*=}"
    shift # past argument=value.
    ;;
    *)
      # unknown option.
    ;;
esac
done

lando_green "Creating database: ${database}";

# Create the database with the default Lando database credentials if
# it doesn't already exist.
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS ${database}; \
                 GRANT ALL PRIVILEGES ON ${database}.* TO 'drupal8'@'%' IDENTIFIED by 'drupal8';"
