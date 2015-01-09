<?php
/**
 * This script reads the default Magento config
 * You do not to set anything else than an access password
 */

if (!$ACCESS_PWD) {
    // !!!IMPORTANT!!! this script needs a access password, put yours here
    $ACCESS_PWD = '';
}

// Where is Magento's config file located
// default is phpminiadmin.php in the documentroot
$config = dirname(__FILE__).'/app/etc/local.xml';


// Read config file and punt in database definition
function mo_c($config, &$DBDEF) {
    // Read Magento configuration
    if (!is_readable($config)) {return false;}

    // Find database properties
    $regkeys='host|username|password|dbname|initStatements';
    $match=array();$regexp="#<({$regkeys})>(<!\[CDATA\[)?([^\]<]*)(\]\]>)?</({$regkeys})>#";
    if (!preg_match_all($regexp, preg_replace("#^.*<connection>\s*([\s\S]+)\s*</connection>.*$#s", "\\1", implode('', file($config))), $match)) {return false;}

    // Create connection array
    $keys = array_combine($match[1], $match[3]);

    // Set variables from the database definition
    if (isset($keys['host'])) {
        $keys['host'] = explode(':', $keys['host']);
        $DBDEF['host'] = $keys['host'][0];
        isset($keys['host'][1]) && ($DBDEF['port'] = $keys['host'][1]);
    }
    if (isset($keys['dbname'])) {$DBDEF['db'] = $keys['dbname'];}
    if (isset($keys['username'])) {$DBDEF['user'] = $keys['username'];}
    if (isset($keys['password'])) {$DBDEF['pwd'] = $keys['password'];}
    if (isset($keys['initStatements'])) {$DBDEF['chset'] = str_replace('SET NAMES ', '', $keys['initStatements']);}

    return true;
}function mo_q() {return isset($_REQUEST['q'])||($_REQUEST['q']='SHOW TABLE STATUS');} // Initial query
$ACCESS_PWD&&mo_c($config, $DBDEF)&&mo_q();
unset($config);
