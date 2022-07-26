<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Configuration file for multi-site support and directory aliasing feature.
 *
 * This file is required for multi-site support and also allows you to define a
 * set of aliases that map hostnames, ports, and pathnames to configuration
 * directories in the sites directory. These aliases are loaded prior to
 * scanning for directories, and they are exempt from the normal discovery
 * rules. See default.settings.php to view how Drupal discovers the
 * configuration directory when no alias is found.
 *
 * Aliases are useful on development servers, where the domain name may not be
 * the same as the domain of the live server. Since Drupal stores file paths in
 * the database (files, system table, etc.) this will ensure the paths are
 * correct when the site is deployed to a live server.
 *
 * To activate this feature, copy and rename it such that its path plus
 * filename is 'sites/sites.php'.
 *
 * Aliases are defined in an associative array named $sites. The array is
 * written in the format: '<port>.<domain>.<path>' => 'directory'. As an
 * example, to map https://www.drupal.org:8080/mysite/test to the configuration
 * directory sites/example.com, the array should be defined as:
 * @code
 * $sites = [
 *   '8080.www.drupal.org.mysite.test' => 'example.com',
 * ];
 * @endcode
 * The URL, https://www.drupal.org:8080/mysite/test/, could be a symbolic link
 * or an Apache Alias directive that points to the Drupal root containing
 * index.php. An alias could also be created for a subdomain. See the
 * @link https://www.drupal.org/documentation/install online Drupal installation guide @endlink
 * for more information on setting up domains, subdomains, and subdirectories.
 *
 * The following examples look for a site configuration in sites/example.com:
 * @code
 * URL: http://dev.drupal.org
 * $sites['dev.drupal.org'] = 'example.com';
 *
 * URL: http://localhost/example
 * $sites['localhost.example'] = 'example.com';
 *
 * URL: http://localhost:8080/example
 * $sites['8080.localhost.example'] = 'example.com';
 *
 * URL: https://www.drupal.org:8080/mysite/test/
 * $sites['8080.www.drupal.org.mysite.test'] = 'example.com';
 * @endcode
 *
 * @see default.settings.php
 * @see \Drupal\Core\DrupalKernel::getSitePath()
 * @see https://www.drupal.org/documentation/install/multi-site
 */

// Different sites setup depending on whether we are running on
// Platform.sh or local Lando
use Symfony\Component\Yaml\Yaml;

if (!empty(getenv('PLATFORM_BRANCH'))) {
    // Multi site configuration for Platform.sh.

    $platformsh = new \Platformsh\ConfigReader\Config();

    if (!$platformsh->inRuntime()) {
        return;
    }

    // The following block adds a $sites[] entry for each subdomain that is defined
    // in routes.yaml.
    // Note that the route retrieved will be fully expanded at this point (so the region,
    // project id, branch name will be already filled in for dev sites).
    // Note also that the corresponding directory in sites will be just up to the first dot
    // in the domain name, so the route "https://uregni.gov.uk.{default}/" will correspond
    // to the sites/uregni directory.
    foreach ($platformsh->getUpstreamRoutes($platformsh->applicationName) as $route) {
        $host = parse_url($route['url'], PHP_URL_HOST);
        if ($host !== FALSE) {
            // At this point, host may be in either of these two forms:
            //  - www.fiscalcommissionni.org
            //  - hatecrimereviewni.org.uk.master-7rqtwti-6tlkpwbr6tndk.uk-1.platformsh.site
            $newhost = str_replace('www.','',$host);
            $subdomain = substr($newhost, 0, strpos($newhost, '.'));
            // Check for domain names that contain dashes and strip them out.
            // (This ensures that sites like mentalhealthchampion-ni.org.uk may be
            // served from sites/mentalhealthchampionni rather than
            // sites/mentalhealthchampion-ni)
            $subdomain = str_replace('-','',$subdomain);
            $sites[$host] = $subdomain;
        }
    }
}

// Running in Lando locally, include appropriate sites file.
if (getenv('LANDO')) {

    $project = Yaml::parseFile('/app/project/project.yml');

    foreach ($project['sites'] as $site_id => $site) {
        if ($site_id == 'mentalhealthchampionni') {
          // Special case for URL that contains a '-'
          $sites['mentalhealthchampion-ni.lndo.site'] = $site_id;
        } else {
          $sites[$site['url'] . '.lndo.site'] = $site_id;
        }
    }

    return $sites;
}
