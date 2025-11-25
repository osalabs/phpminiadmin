### changes in phpMiniAdmin 1.9.251125 (latest)
- reverted back from str_starts_with to strpos to support PHP 7

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

### changes in phpMiniAdmin 1.9.170203
- added "compact view" option. If checked - width/height of grid cells limited, so you can see more rows/columns in case your data is large. And in this mode you can double-click on cells to "expand" just that particular cell.

### changes in phpMiniAdmin 1.9.170117
- greatly optimized memory usage for large result sets (especially in export)

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
