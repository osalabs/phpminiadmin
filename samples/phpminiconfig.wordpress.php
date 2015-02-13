<?php
/**
 * This script reads the default Wordpress config
 * You do not to set anything else than an access password
 */

if (!$ACCESS_PWD) {
    // !!!IMPORTANT!!! this script needs a access password, put yours here
    $ACCESS_PWD = '';
}

// Where is Wordpress config file located
// default is phpminiadmin.php in the documentroot
$config = dirname(__FILE__).'/wp-config.php';


// Read config file and punt in database definition
function wp_c($config, &$DBDEF) {
    // Read Wordpress configuration
    if (!is_readable($config)) {return false;}

    // Find database properties
    $match=array();$regexp="#\(\s*['\"]DB_(USER|NAME|HOST|PASSWORD|CHARSET)['\"]\s*,\s*['\"]([^'\"]+)['\"]\s*\);#";
    if (!preg_match_all($regexp, implode('', file($config)), $match)) {return false;}

    // Create connection array
    $keys = array_combine($match[1], $match[2]);

    // Set variables from the database definition
    if (isset($keys['HOST'])) {
        $keys['HOST'] = explode(':', $keys['HOST']);
        $DBDEF['host'] = $keys['HOST'][0];
        isset($keys['HOST'][1]) && ($DBDEF['port'] = $keys['HOST'][1]);
    }
    if (isset($keys['NAME'])) {$DBDEF['db'] = $keys['NAME'];}
    if (isset($keys['USER'])) {$DBDEF['user'] = $keys['USER'];}
    if (isset($keys['PASSWORD'])) {$DBDEF['pwd'] = $keys['PASSWORD'];}
    if (isset($keys['CHARSET'])) {$DBDEF['chset'] = $keys['CHARSET'];}

    return true;
}function wp_q() {return isset($_REQUEST['q'])||($_REQUEST['q']='SHOW TABLE STATUS');} // Initial query
$ACCESS_PWD&&wp_c($config, $DBDEF)&&wp_q();
unset($config);
