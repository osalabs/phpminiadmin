![](http://phpminiadmin.sourceforge.net/img/scr_select_from_table.gif)

[other screenshots are here](http://sourceforge.net/project/screenshots.php?group_id=181023)

## How to Install
- Download phpminiadmin.php file
- Copy/Upload it to your webserver public directory (www or public_html or whatever...)
- Open in your browser http:// yoursite.com/phpminiadmin.php

For additional security you may edit phpminiadmin.php file and set some password (see $ACCESS_PWD variable)

## Links
- [Screenshots](http://sourceforge.net/project/screenshots.php?group_id=181023)
- [Live demo](http://phpminiadmin.sourceforge.net/phpminiadmin.php) (pwd: pmaiscool)
- [Download latest version](https://sourceforge.net/projects/phpminiadmin/files/latest)
- [SourceForge page of the phpMiniAdmin](http://phpminiadmin.sourceforge.net/)
- [Google Code page of the phpMiniAdmin](http://code.google.com/p/phpminiadmin/)
- [My Twitter](http://twitter.com/#!/osalabs)
- [My website](http://osalabs.com)

## Change Log
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

