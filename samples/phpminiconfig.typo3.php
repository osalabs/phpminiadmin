<?php
/**
 * This script reads the default typo3 config (for typo3 version 6)
 * You do not to set anything else than an access password
 */

if (!$ACCESS_PWD) {
    // !!!IMPORTANT!!! this script needs a access password, put yours here
    $ACCESS_PWD = '';
}

// Where the typo3 config file is located
// default is phpminiadmin.php in the documentroot
$config = dirname(__FILE__).'/typo3conf/LocalConfiguration.php';

// Read config file and punt in database definition
function typo3_c($config, &$DBDEF) {
    // Read typo3 configuration
    
    if (!is_readable($config)) {return false;}
    //typo3 config is an array of arrays, () evaluates the included file as a function 
    $t3_conf = (include($config));
    
    $DBDEF['db'] = $t3_conf['DB']['database'];

    $DBDEF['user'] = $t3_conf['DB']['username'];
    $DBDEF['pwd'] = $t3_conf['DB']['password'];
    $DBDEF['host'] = $t3_conf['DB']['host'];

    return true;
}function typo3_q() {return isset($_REQUEST['q'])||($_REQUEST['q']='SHOW TABLE STATUS');} // Initial query
$ACCESS_PWD&&typo3_c($config, $DBDEF)&&typo3_q();
unset($config);
