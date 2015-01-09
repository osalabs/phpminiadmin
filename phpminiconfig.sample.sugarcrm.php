<?php
/**
 * This script reads the default SugarCRM config
 * You do not to set anything else than an access password
 */

if (!$ACCESS_PWD) {
    // !!!IMPORTANT!!! this script needs a access password, put yours here
    $ACCESS_PWD = '';
}

// Where is SugarCRM config file located
// default is phpminiadmin.php in the documentroot
$config = dirname(__FILE__).'/config.php';


// Read config file and punt in database definition
function sc_c($config, &$DBDEF) {
    // Read SugarCRM configuration
    if (!is_readable($config)) {return false;}

    // Find database properties
    $match=array();$regexp="#['\"]db_(host_name|user_name|password|name|port)['\"]\s*=>\s*['\"]([^'\"]+)['\"]#";
    if (!preg_match_all($regexp, implode('', file($config)), $match)) {return false;}

    // Create connection array
    $keys = array_combine($match[1], $match[2]);

    // Set variables from the database definition
    if (isset($keys['host_name'])) {$DBDEF['host'] = $keys['host_name'];}
    if (isset($keys['port'])) {$DBDEF['port'] = $keys['port'];}
    if (isset($keys['user_name'])) {$DBDEF['user'] = $keys['user_name'];}
    if (isset($keys['name'])) {$DBDEF['db'] = $keys['name'];}
    if (isset($keys['password'])) {$DBDEF['pwd'] = $keys['password'];}

    return true;
}function sc_q() {return isset($_REQUEST['q'])||($_REQUEST['q']='SHOW TABLE STATUS');} // Initial query
$ACCESS_PWD&&sc_c($config, $DBDEF)&&sc_q();
unset($config);

