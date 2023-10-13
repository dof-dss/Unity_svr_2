#!/usr/bin/env bash

echo "Starting unity theme update"

# Define the path to the 'sites' directory
sites_dir="/app/project/sites"

if [ "$#" -gt 0 ]; then
    # If site names are provided, process them
    IFS=',' read -r -a site_names <<< "$1"
else
    # If no site names are provided, get all site names
    site_names=($(ls -1 "$sites_dir"))
fi

for site_name in "${site_names[@]}"; do
    # Trim leading and trailing whitespace from site name
    site_name=$(echo "$site_name" | xargs)

    # Skip empty site names
    if [ -z "$site_name" ]; then
        continue
    fi

    site_path="$sites_dir/$site_name"
    themes_dir="$site_path/themes/${site_name}_theme"

    if [ -d "$themes_dir" ]; then
        echo "Running npm install in $themes_dir"
        cd "$themes_dir"

        npm install --silent --no-progress
        npm install nicsdru_unity_theme --silent --no-progress
        npm run build
    else
        echo "Themes directory not found for $site_name"
    fi
done
