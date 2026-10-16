# Interim Drupal 11 compatibility

This copy comes from Unity 1's `web/modules/interim/filelog`, last changed in
commit `0a85805fcaa54ed246cd3a6cdce196b26b2659ad`.
It retains File Log while the upstream Composer package declares Drupal 9/10
compatibility. The local interim copy was explicitly authorised for Unity 2.

Unity 2 additionally corrects PHPDoc types, initialises stream-copy state and
handles empty files and end-of-file at chunk boundaries. The stream-copy checks
cover plain files and gzip output, and site verification checks an actual log
write without printing existing log contents.

Run the checks inside DDEV:

```sh
ddev exec drush -l mentalhealthchampionni php:script /var/www/html/scripts/drupal11/test-filelog.php
ddev exec drush -l mentalhealthchampionni php:script /var/www/html/scripts/drupal11/verify.php
```

The interim module is included in the CI coding standards, deprecated-code and
disallowed-function checks. Replace it with a compatible upstream release when
one is available and verified.
