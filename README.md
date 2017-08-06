![](http://phpminiadmin.sourceforge.net/img/scr_select_from_table.gif)

[other screenshots are here](https://sourceforge.net/projects/phpminiadmin/#screenshots)

## How to Install
- Download phpminiadmin.php file
- Copy/Upload it to your webserver public directory (www or public_html or whatever...)
- Open in your browser http:// yoursite.com/phpminiadmin.php

For additional security you may edit phpminiadmin.php file and set some password (see $ACCESS_PWD variable)

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
### changes in phpMiniAdmin 1.9.170203 (latest)
- added "compact view" option. If checked - width/height of grid cells limited, so you can see more rows/columns in case your data is large. And in this mode you can double-click on cells to "expand" just that particular cell.

### changes in phpMiniAdmin 1.9.170117
- greatly optimized memory usage for large result sets (especially in export)

### changes in phpMiniAdmin 1.9.161116
- added ability to dump exports right on server, without need to download
- added ability to import from .sql or .gz file right on server, without need to upload one
- fixed export, now it won't dump data for VIEWs

[see older changes in changelog](changelog.md)

