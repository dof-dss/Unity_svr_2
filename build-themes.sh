#!/usr/bin/env bash

source ~/.bashrc
echo "Starting unity theme update"

# Defaults
sites_dir="/app/project/sites"
site_names=()

# Parse named parameters
while [[ $# -gt 0 ]]; do
    key="$1"
    case $key in
        --sites-dir)
            sites_dir="$2"
            shift
            shift
            ;;
        --site-names)
            IFS=',' read -r -a site_names <<< "$2"
            shift
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# If no site names provided, process all sites
if [ ${#site_names[@]} -eq 0 ]; then
    site_names=($(ls -1 "$sites_dir"))
fi

# Process each site
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
