# Interim File delete module

Retained from the established interim fork of
https://git.drupalcode.org/project/file_delete_ui at commit
`56a1b3ac1676b2df5812f9d65911344b2dc7d75a` (Drupal 11 compatible).

This code is retained only so sites whose active configuration still enables
file_delete_ui can uninstall it through Drupal. Uninstall the module manually
with Drush for each site before configuration import. Exported configuration
must not enable this module. Remove this interim copy only after every site's
database has completed the uninstall; removing the code first recreates the
missing module error.
