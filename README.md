![](http://phpminiadmin.sourceforge.net/img/scr_select.gif)

[other screenshots](https://sourceforge.net/projects/phpminiadmin/#screenshots)

## How to Install
- Download [phpminiadmin.php](https://raw.github.com/osalabs/phpminiadmin/master/phpminiadmin.php) file
  - example: `wget https://raw.github.com/osalabs/phpminiadmin/master/phpminiadmin.php`
- Copy/Upload it to your webserver public directory (www or public_html or whatever...)
- Open in your browser `http://yoursite.com/phpminiadmin.php`

**Recommended:** For additional security you may edit phpminiadmin.php file and set some password (see `$ACCESS_PWD` variable)

### Dependencies
The only required php extension is `mysqli`. Therefore if you got a white screen install it:

`sudo apt-get install php-mysql` on Debian
or enable `extension=php_mysqli.dll` in php.ini on Windows

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

### changes in phpMiniAdmin 1.9.240727 (latest)
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

### changes in phpMiniAdmin 1.9.210705
- removed use of function `get_magic_quotes_gpc` deprecated since PHP 7.4.0
- utf8mb4 is now default charset
- tested in PHP 8, cleaned up several PHP Warnings

[see older changes in changelog](changelog.md)

