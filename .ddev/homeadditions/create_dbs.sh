#!/bin/bash

# Iterate every symlink (which web create for each site) under web/sites and create a database with the default
# DDEV database credentials if it doesn't already exist.
echo "Creating databases"
for dir in $(find /var/www/html/web/sites/ -mindepth 1 -maxdepth 1 -type l) ; do
  database=${dir##*/} ;

  # Ignore the standard 'default' folder under web/sites.
  if [ $database != 'default' ]; then
     # Create the standard databases if they don't already exist.
     mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS ${database}; \
                      GRANT ALL PRIVILEGES ON ${database}.* TO '$DB_USER'@'localhost' IDENTIFIED by '$DB_PASS';
                      GRANT ALL PRIVILEGES ON ${database}.* TO '$DB_USER'@'%' IDENTIFIED by '$DB_PASS';" | cut -f1 -d":";
  fi
done
