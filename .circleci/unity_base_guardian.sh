#!/bin/bash
# Checks that Unity Base remains true to it's origins and doesn't contain any project files.

if [ -d "project" ]
then
    echo "Project directory found."
    exit 1
fi

if [ -e ".lando.yml" ]
then
    echo "Lando config file found."
    exit 1
fi

