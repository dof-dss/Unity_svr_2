#!/usr/bin/env bash
echo "Starting unity theme update"

# Define the path to the 'sites' directory
sites_dir=${1:-"/app/project/sites"}

# Loop through each directory in the 'sites' directory
for site in "$sites_dir"/*; do
    site_name=$(basename "$site")
    themes_dir="$site/themes/${site_name}_theme"

    if [ -d "$themes_dir" ]; then
        echo "Running npm install in $themes_dir"
        cd "$themes_dir"

        # if [ -d node_modules ]; then
        #   rm -rf node_modules
        # fi

        npm install --silent --no-progress
        npm install nicsdru_unity_theme --silent --no-progress
        npm run build
    fi
done
