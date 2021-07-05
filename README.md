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
- [Download latest version](https://sourceforge.net/projects/phpminiadmin/files/latest)
- [SourceForge page of the phpMiniAdmin](http://phpminiadmin.sourceforge.net/)
- [My Twitter](http://twitter.com/#!/osalabs)
- [My website](http://osalabs.com)

## Change Log

### changes in phpMiniAdmin 1.9.210705 (latest)
- removed use of function `get_magic_quotes_gpc` deprecated since PHP 7.4.0
- utf8mb4 is now default charset
- tested in PHP 8, cleaned up several PHP Warnings

### changes in phpMiniAdmin 1.9.210129
- limited max packet size during export to aviod PHP memory exhausted errors on huge tables

### changes in phpMiniAdmin 1.9.200928
- added ability to setup SSL connection (define at least "ssl_ca" in `$DBDEF`)

### changes in phpMiniAdmin 1.9.190822
- added ability to set socket for db connection

### changes in phpMiniAdmin 1.9.170730
- fixed potential XSS in database names and fields [#28](https://github.com/osalabs/phpminiadmin/issues/28)
- db NULLs now displayed in italic to distinguish from "NULL" text
- misc formatting adjustments

[see older changes in changelog](changelog.md)

