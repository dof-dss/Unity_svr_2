# Unity sites codebase

[![CircleCI](https://circleci.com/gh/dof-dss/nicsdru_unity.svg?style=svg)](https://circleci.com/gh/dof-dss/nicsdru_unity)

This source code is for the Unity sites. It is built with Drupal 8 in a multisite configuration.

It is hosted on platform.sh.

Continuous Integration and Deployment services are provided by Circle CI.

## Getting started

We recommend Lando for local development. To get started, ensure you have the following installed:

1. Lando [https://docs.devwithlando.io/](https://docs.devwithlando.io/)
2. Composer [https://getcomposer.org/](https://getcomposer.org/)
3. Platform CLI tool [https://docs.platform.sh/development/cli.html](https://docs.platform.sh/development/cli.html)

- Clone this repo
- at the command line, 'cd' into your new directory
- Set up your local environment variables: `cp .lando/config/.env.sample .lando/config/.env`
- Env vars are divided into safe values for local development (eg: Lando defaults) and sensitive values which
  you will need to source from a project member or from one of the active project environments. In order to
  just get you local Unity sites up and running the minimum that you will have to do is to set 'HASH_SALT' to
  a random string e.g. 'ahsgsfdteyionuydythdop'
- `lando start`
- `lando composer install`


Or, if available, you may also fetch the database and import this:

`platform db:dump` (you'll need to select project, environment and required site e.g. 'uregni')

`lando db-import -d <sitename> -f <downloaded sql file>` (where 'sitename' may be 'uregni', 'liofa' etc)

## Preparing to run migrations

You will first need to get hold of a Drupal 7 database dump for your chosen site to act as the source of the migration.

Taking uregni as an example, the database dump can be downloaded from Platform.sh using the 'platform db:dump' command
and selecting 'Software-responsive' and 'uregni'.

Files may be downloaded from Platform.sh using the 'platform mount:download' command, selecting 'Software-responsive'
and then download files from 'public_html/sites/<sitename>/files' e.g. for ODSCNI files would be downloaded from
'public_html/sites/odscni/files'.

The downloaded files should be placed in the imports/files directory e.g. imports/files/sites/odscni.

## Migrating a site for the first time

1. Once you have downloaded the database and files as described above, you will need to import the database into the Drupal 7 database
for your chosen site. Using our example site this will be 'uregni_legacy'.
Note that the -d sitename must have a '_legacy' suffix, please make sure that you do not overwrite your Drupal 8 database by mistake !:
`lando db-import -d <sitename>_legacy -f <downloaded db>`

2. Make sure that the following modules are installed in your Drupal 8 site: migrate_upgrade, migrate_plus, migrate_tools

3. Run the migrate-upgrade command to generate migrations for you (run this from inside your web/sites/sitename directory).
   Here is an example for the odscni site:

   lando drush migrate-upgrade --legacy-db-url=mysql://drupal8:drupal8@database/odscni_legacy
   --legacy-root=https://www.odscni.org.uk/ --configure-only

4. You will now be able to see migrations in your database (lando drush migrate-status). The next step is to export the migrations
which may be done by exporting config (land drush config-export). You will now see lots of migration Yaml files (starting
   with 'migrate_plus...') in your config/<sitename> directory.

5. Create a new custom module for your site. An example of this is web/sites/uregni/modules/custom/uregni_migrations. Your new module
should have a config/install directory.

6. Move the migrations that you exported from the config/<sitenam> directory into the config/install directory of your new module using
a command like this:
   mv migrate_plus.migration* ../../../web/sites/odscni/modules/custom/odscni_migrations/config/install

7. The migrations should now be controlled from your custom module. (You will need to remove some of the migrations like 'field_instance'
and 'node_type' as these only need to be run once).


## Re-running migrations

1. Import the database into the Drupal 7 database for your chosen site. Using our example site this will be 'uregni_legacy'.
Note that the -d sitename must have a '_legacy' suffix, please make sure that you do not overwrite your Drupal 8 database by mistake !:
`lando db-import -d <sitename>_legacy -f <downloaded db>`

2. Import config:
`lando drush import-config`

3. Install the unity_file_migrations module and any site specific migration modules e.g. uregni_migrations

3. Make sure that you are in the appropriate site directory e.g. web/sites/uregni and run this command:
`lando drush migrate-import --group=migrate_drupal_7`

4. You may need to run the 'migrate-import' command a few times until it completes.

5. When all content has been migrated, make sure that all nodes are published if appropriate (see note below):
`lando drush -l uregni post-migrate-publish`

6. Import config again:
`lando drush import-config`

7. Import blocks saved in config using the 'structure sync' module (select option 1 - 'Full'):
`lando drush import-blocks`

N.B. You should only run the publish status script (step 7) after ALL node and revision migrations have completed.
This process will correctly set current revision and publish status for all nodes but it will create new revisions.
This means that once this has been run there should be no more 'top up' migrations, the only option is to roll back
all revision and node migrations and start from scratch.


## Code workflow

Like the popular git-flow workflow, but without the more complex elements:

- `development` bleeding-edge. All feature branches originate from here.
- `master` stable, automatically deployed to platform.sh. Release tags should originate from here.

Preferred feature branch naming convention: `TICKET_REF-short-desc`, for example: `D8UN-123-event-listing`

We highly recommend developers use a tool such as [Talisman](https://github.com/thoughtworks/talisman) to ensure they do not commit potentially sensitive material into the codebase.

API keys, auth tokens or other credentials values *must* be stored as environment variables and never stored in the codebase itself.

## Talisman pre-commit hooks

- We *strongly* recommend developers use Talisman when working on this project.
- Talisman validates the outgoing change set for things that look suspicious - such as authorization tokens and private keys.

[Installation instructions](https://github.com/thoughtworks/talisman/#installation-as-a-global-hook-template)

## Project structure

Some key project directories and/or files:

```
└── .circleci/ (Circle CI configuration and supporting files)
├── .lando/ (Lando configuration files)
├── .lando.yml + .lando.local.yml (Lando service definition files)
├── .platform/ (platform.sh routes and services config)
├── .platform.app.yaml (platform.sh application config)
├── docker-compose.yml (used by Lando for non-supported services)
├── composer.json (defines project dependencies)
├── composer.lock (what composer install uses when running, ensure this is always in sync with composer.json)
├── config/ (configuration management folder)
├── phpcs.sh (shell script to simplify invocation of PHPCS tool)
├── vendor/ (third party dependencies and libraries; sourced by composer)
├── web/ (docroot folder)
├── web/core (Drupal core; don't alter except via composer patches)
├── web/modules/contrib (community modules; don't alter except via composer patches)
├── web/modules/custom (custom code; sourced from other repository by composer)
├── web/modules/origins (common internal custom modules; sourced from other repository by composer)
├── web/themes/custom/nicsdru_origins_theme (custom base theme) composer)
└── web/sites/sites.php (Drupal multi site config file)
```

## Contribution

All changes **must** be submitted with an appropriate pull request (PR) in GitHub. Direct commits to `master` or `development` are not normally permitted.

## Adding new sites to the multi site codebase

- Set up a new database in .platform/services.yaml (just like 'uregni' or 'liofa')
- Add your new db to the 'relationships' section of .platform.app.yaml
- Add new short site name to the 'deploy' hook loop in .platform.app.yaml (you will see the other sites listed)
- Add new routes to .platform/routes.yaml, one for domain name of the new site and another for the www redirect
(use 'uregni' as an example)
- Create a new directory for your site under web/sites. Note that the directory name should be the first part of the
domain name (short sitename) up until the first dot, so if your domain name is 'uregni.gov.uk' then the directory
name should be just 'uregni'
- Copy a settings.php file into your new web/sites/<short sitename> directory from web/sites/uregni
- Create a new directory /config/<short sitename> and within this create 4 new directories, config, hosted, local and production. Place a .gitkeep file in the config/<sitename> directory, and place a .htaccess file into each of the sub directories
- Edit the top level .lando.yml file and add a new local site url (with '.lndo.site' suffix) under proxy/appserver
e.g. uregni.gov.uk.lndo.site
- If necessary, add a new Solr core in .lando.yml (just copy uregni_solr and give it a different name, this will be the 'Solr host' when
configuring the server in search_api)
- If necessary, add a new Solr core for Platform.sh in .platform/service.yaml (add a core and an endpoint by copying the config for
'uregni_index' and 'uregni') - after doing this add a new relationship in .platform.app.yaml (following the example of 'uregnisolr')
- When creating your solr server in search_api use 'standard' connector, 'solr' as solr host, and short sitename as solr core - also
under 'Advanced Server Configuration' set the solr.install.dir to '../../..'
- Edit web/sites/sites.lando.php and add a new mapping from your local url (with '.lndo.site' suffix) to the short site name
- N.B. After adding a new site, you will need to run 'lando rebuild' before you can access your new site
- Run through the local site install
- Enable and configure the config_split module
- Export the config, push to the development and then merge into master
- Run through the Drupal site install on the master branch
- Connect to the Platform server using `platform ssh` and clear the cache for each of the sites
- If there are any uuid errors in the deploy log, update the system.site uuid to match that in system.site.yml
  `drush cset system.site uuid <uuid>`
- If you get the error 'Can not delete the default language' update the language.entity.en uuid to match that in language.entity.en.yml `drush cset language.entity.en uuid <uuid>`

Under multi site, Lando commands may be run as follows:
lando drush -l uregni cr
lando drupal --uri=http://uregni.gov.uk.lndo.site
lando -h uregni mysql

After connecting to the Platform server using 'platform ssh', drush commands may be run as follows (in the /app/web directory):
../vendor/bin/drush -l uregni cr

Also, if you run platform CLI commands like 'platform sql' you will be asked to choose between the multi sites.

- N.B. "There can be only one" - because the local Lando site URLs take the form 'uregni.gov.uk.lndo.site' and do not include the
Lando app name, you may only have one set of sites installed on your local machine i.e. it is not possible to clone this repo
into /apps/unity and run 'lando start' and then subsequently clone it into /apps/unity2 and run 'lando start' again as the
site URLs will be duplicated and Lando will attempt to set up 'uregni.gov.uk.lndo.site' pointing to both.

## Uploading files to platform.sh

- Run the command platform mount:upload.
- Select the project and environment and then select option 3 for web/files.
- Supply the path to your local web/files directory
- Any new files in your local web/files directory will be uploaded
- Note that this is a 'non destructive' upload, so if you have deleted any files locally they will not be deleted on the
  server by the 'mount:upload' command
- Note also that it is fine if your local web/files directory only has one subdirectory for one of the multi sites (like 'liofa'
  for example) - in this case new files will just be uploaded from your web/files/liofa directory and none of the other
  sub-directories on the server will be affected.

## Running Nightwatch tests
- Nightwatch tests may appear in the /tests directory of any module
- Nightwatch tests may be run easily from Lando, the following command is a good example:

lando nightwatch uregni ../modules/origins/origins_workflow/tests/src/Nightwatch/Tests/AdminTest.js

Note that a Unity site name must appear after 'nightwatch' (this could equally be 'liofa' or 'fictcommission').
The path to the Nightwatch test is relative to web/core.

# Licence
Unless stated otherwise, the codebase is released under [the MIT License](http://www.opensource.org/licenses/mit-license.php). This covers both the codebase and any sample code in the documentation.
