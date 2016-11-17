![](http://phpminiadmin.sourceforge.net/img/scr_select_from_table.gif)

[other screenshots are here](http://sourceforge.net/project/screenshots.php?group_id=181023)

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
### changes in phpMiniAdmin 1.9.161116
- added ability to dump exports right on server, without need to download
- added ability to import from .sql or .gz file right on server, without need to upload one
- fixed export, now it won't dump data for VIEWs

### changes in phpMiniAdmin 1.9.160705
- screen appearance changes/fixes
- added buttons which inserts standard "template" queries for Select, Insert, Update, Delete
- disabled counting total number of records for pagination as it slows down response, especially on large datasets. Set `$IS_COUNT=true` to enable this feature back.

### changes in phpMiniAdmin 1.9.160630

- all queries now base64 encoded to bypass applications firewalls. Note, **IE10 browser or later required from now**
- SHOW TABLE STATUS fixed to display views, also Comments column added
- fixed Settings/Login/Logoff

### changes in phpMiniAdmin 1.9.150729

- switched to MySQLi because MySQL extension deprecated in PHP7

### changes in phpMiniAdmin 1.9.150108

- httponly cookies so your plain password cannot be stolen by javascript
- export disables foreign key checks
- ask for super privilege(otherwise you get a error on import)
- added support for a config file with credentials

### changes in phpMiniAdmin 1.9.141219

- added: total count of records displayed for selects with pagination (example: 50 out of 104)
- fixed: labels on forms, so inputs can be correctly narrated for blind users

### changes in phpMiniAdmin 1.9.140405

- fixed: couple low risk XSS vulnerabilities
- fixed: CSV export in UTF-8
- added: ability to quickly create new database without SQL knowledge
- added: autofocus to login pwd field
- added: some minor compatibility changes for PHP 4.x
- changed: yellow row highlight removed if clicked again
- changed: if field contains binary data (char codes < 32), only first 16 hex will be displayed (if you want to dump full content - select just one this field)

### changes in phpMiniAdmin 1.8.120510

- fixed: Undefined offset in get_rand_str
- fixed: automatic relogin on XSS error
- added: page navigator at the bottom
- added: row counts, table sizes on the table list
- added: MIT license
- added: query history via browser's localStorage
- added: if database empty - show databases
- added: after import - show tables
- changed: moved from html 4.01 to html 5
- changed: simplified settings form

### changes in phpMiniAdmin 1.7.111025

- fixed: unable to relogin on XSS error
- fixed: truncate button doesn't work
- minor changes in text labels and css styles

### changes in phpMiniAdmin 1.7.110429

- added: import/export to/from gzip compressed files (.gz)

