# Unity base

This repository contains Drupal core along with contrib and shared custom modules or themes for Unity sites. It is never
deployed direct to a hosting environment and instead is used to create 'server specific' forks.

When a core or contrib update is required for Unity sites it is first performed against this repository, pushed to
Github and then each 'server instance' fork will pull from this upstream source. It is imperative that any
changes to the files within this repository are not altered directly on the server instance fork as any future pulls
could possibly overwrite those changes.

For information on the Unity2 project please see:
https://digitaldevelopment.atlassian.net/wiki/spaces/D8UN/pages/2183757845/Unity2

## Licence
Unless stated otherwise, the codebase is released under [the MIT License](http://www.opensource.org/licenses/mit-license.php). This covers both the codebase and any sample code in the documentation.
