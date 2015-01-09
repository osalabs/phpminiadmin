<?php
/**
 * This script reads the default Vtiger config
 * You do not to set anything else than an access password
 */

if (!$ACCESS_PWD) {
    // !!!IMPORTANT!!! this script needs a access password, put yours here
    $ACCESS_PWD = '';
}

// Where is Vtiger's config file located
// default is phpminiadmin.php in the documentroot
$config = dirname(__FILE__).'/config.inc.php';


// Read config file and punt in database definition
function crm_c($config, &$DBDEF) {
    // Read Vtiger configuration
    if (!is_readable($config)) {return false;}

    // Find database properties
    $match=array();$regexp="#\[\s*['\"]db_(server|port|username|password|name)['\"]\s*\]\s*=\s*['\"]([^'\"]+)['\"]\s*;#";
    if (!preg_match_all($regexp, implode('', file($config)), $match)) {return false;}

    // Create connection array
    $keys = array_combine($match[1], $match[2]);

    // Set variables from the database definition
    if (isset($keys['server'])) {$DBDEF['host'] = $keys['server'];}
    if (isset($keys['port'])) {$DBDEF['port'] = str_replace(':', '', $keys['port']);}
    if (isset($keys['name'])) {$DBDEF['db'] = $keys['name'];}
    if (isset($keys['username'])) {$DBDEF['user'] = $keys['username'];}
    if (isset($keys['password'])) {$DBDEF['pwd'] = $keys['password'];}

    return true;
}function crm_q() {return isset($_REQUEST['q'])||($_REQUEST['q']='SHOW TABLE STATUS');} // Initial query
$ACCESS_PWD&&crm_c($config, $DBDEF)&&crm_q();
unset($config);
