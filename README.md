![](http://phpminiadmin.sourceforge.net/img/scr_select.gif)

[other screenshots](https://sourceforge.net/projects/phpminiadmin/#screenshots)

## How to Install
- Download [phpminiadmin.php](https://raw.github.com/osalabs/phpminiadmin/master/phpminiadmin.php) file
  - example: `wget https://raw.github.com/osalabs/phpminiadmin/master/phpminiadmin.php`
- Copy/Upload it to your webserver public directory (www or public_html or whatever...)
- Open in your browser `http://yoursite.com/phpminiadmin.php`

**Recommended:** For additional security you may edit phpminiadmin.php file and set some password (see `$ACCESS_PWD` variable)

### Dependencies
phpminiadmin prefers PDO if the `pdo_mysql` driver is present. 

Otherwise, if PDO is not available - `mysqli` must be enabled. 
If you encounter a white screen install/enable it:
  - `sudo apt-get install php-mysql` on Debian
  - or enable `extension=php_mysqli.dll` in php.ini on Windows
  - restart your webserver

## Config file (optional)

You can also create phpminiconfig.php in the same directory as phpminiadmin.php with database credentials or password.
This way you can easily install future releases of phpminiadmin.php

In the directory samples you'll find phpminiconfig.php for known OpenSource packages

- See phpminiconfig.php for an empty example
- See phpminiconfig.magento.php to read Magento its app/etc/local.xml ($ACCESS_PWD is required)
- See phpminiconfig.sugarcrm.php to read SugarCRM its config.php ($ACCESS_PWD is required)
- See phpminiconfig.wordpress.php to read Wordpress its wp-config.php ($ACCESS_PWD is required)
- See phpminiconfig.vtiger.php to read Vtiger its config.inc.php ($ACCESS_PWD is required)

## Links
- [Screenshots](http://sourceforge.net/project/screenshots.php?group_id=181023)
- [Live demo](http://phpminiadmin.sourceforge.net/phpminiadmin.php) (pwd: pmaiscool)
- [Download latest version](https://raw.githubusercontent.com/osalabs/phpminiadmin/master/phpminiadmin.php)
- [SourceForge page of the phpMiniAdmin](http://phpminiadmin.sourceforge.net/)
- [My X/Twitter](https://x.com/osalabs)
- [My website](http://osalabs.com)

## Change Log

### changes in phpMiniAdmin 1.9.251125 (latest)
- reverted back from str_starts_with to strpos to support PHP 7
- fix if does not have permission to run SHOW DATABASES
- moved work with session under $_SESSION['phpMiniAdmin'], so it does not conflict with other applications sessions

### changes in phpMiniAdmin 1.9.240801
- fixed one php short open tag

### changes in phpMiniAdmin 1.9.240727
- support for PHP 8.3 (cleaned up some PHP Warnings, deprecations)
- enhancements:
  - multiple db servers support - define server's configs via `$DBSERVERS` and quickly switch between servers via top navbar dropdown
  - "ps" menu item in top navbar - shortcut for "show processlist"
  - "SHOW TABLE STATUS" now works quicker because emulated via select from `information_schema.TABLES`
  - "WITH" (Common Table Expressions (CTEs)) support
  - moved include for phpminiconfig a bit further, so it allows override more things
- security improvements:
  - `$ACCESS_PWD` now is enforced except for local usage
  - added by default "SET GLOBAL local_infile=0" to prevent unwanted use of `LOAD DATA LOCAL INFILE`. Controlled by `$IS_LOCAL_INFILE` on the beginning of the script.

[see older changes in changelog](changelog.md)
