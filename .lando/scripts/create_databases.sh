#!/bin/bash

. /helpers/log.sh

# Iterate every folder under web/sites and create a database with the default
# Lando database credentials if it doesn't already exist.
for dir in $(find /app/web/sites/ -mindepth 1 -maxdepth 1 -type d) ; do
  database=${dir##*/} ;

  # Ignore the standard 'default' folder under web/sites.
  if [ $database != 'default' ]; then
    lando_green "Creating databases ${database} and ${database}_legacy";
     # Create the Drupal 8 and Drupal 7 legacy databases with the default Lando
     # credentials if they don't already exist.
     mysql -uroot -e "CREATE DATABASE IF NOT EXISTS $database; \
                      GRANT ALL PRIVILEGES ON $database.* TO 'drupal8'@'%' IDENTIFIED by 'drupal8'; \
                      CREATE DATABASE IF NOT EXISTS ${database}_legacy; \
                      GRANT ALL PRIVILEGES ON ${database}_legacy.* TO 'drupal8'@'%' IDENTIFIED by 'drupal8';" | cut -f1 -d":";
  fi
done
