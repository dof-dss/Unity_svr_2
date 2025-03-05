#!/bin/bash

# Iterate every symlink (which web create for each site) under web/sites and create a database with the default
# Lando database credentials if it doesn't already exist.
echo "Creating legacy databases"
for dir in $(find /var/www/html/web/sites/ -mindepth 1 -maxdepth 1 -type l) ; do
  database=${dir##*/} ;

  # Ignore the standard 'default' folder under web/sites.
  if [ $database != 'default' ]; then
     # Create the Drupal 7 legacy databases if they don't already exist.
     mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS ${database}_legacy; \
                      GRANT ALL PRIVILEGES ON ${database}_legacy.* TO '$DB_USER'@'localhost' IDENTIFIED by '$DB_PASS';" | cut -f1 -d":";
  fi
done
