#!/bin/bash

. /helpers/log.sh

# If the first command option is missing then dump every database.
if [ -z "$1" ]; then
  for dir in $(find /app/web/sites/ -mindepth 1 -maxdepth 1 -type d) ; do
    database=${dir##*/} ;

    # Ignore the standard 'default' folder under web/sites.
    if [ $database != 'default' ]; then
        lando_green "Exporting database: ${database}"
        mysqldump --opt --user=${USER} --host=${HOST} --port=${PORT} --databases $database > `date +%Y-%m-%d`.$database.sql
    fi
  done
else
  # Export a single database.
  lando_green "Exporting database: ${1}"
  mysqldump --opt --user=${USER} --host=${HOST} --port=${PORT} --databases ${1} > `date +%Y-%m-%d`.${1}.sql
fi



