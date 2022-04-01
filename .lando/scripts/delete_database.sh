#!/bin/bash

. /helpers/log.sh

# Parse command options & flags.
for i in "$@"
do
case $i in
    -n=*|--name=*)
    database="${i#*=}"
    shift # past argument=value
    ;;
    *)
      # unknown option
    ;;
esac
done

# Ensure we are not deleting anything critical!
if { [ $database = 'mysql' ] || [ $database = 'information_schema' ] || [ $database = 'performance_schema' ]; }; then
  lando_red "I'm sorry Dave, I'm afraid I can't do that"
else
  lando_green "Deleting database: ${database}";

  # Drop the database if it exists.
  mysql -uroot -e "DROP DATABASE IF EXISTS ${database}";
fi
