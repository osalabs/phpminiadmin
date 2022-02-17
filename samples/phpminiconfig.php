<?php
$ACCESS_PWD=''; #!!!IMPORTANT!!! this is script access password, SET IT if you want to protect you DB from public access

$authPage = ""; // Include your own Authenticator File if exist.

// date_default_timezone_set("Asia/Bangkok");
// ini_set('display_errors',1);

 #DEFAULT db connection settings
 # --- WARNING! --- if you set defaults - it's recommended to set $ACCESS_PWD to protect your db!
 $DBDEF=array(
 'user'=>'',#required
 'pwd'=>'', #required
 'db'=>'',  #optional, default DB
 'host'=>'',#optional
 'port'=>'',#optional
 'socket'=>'',#optional
 'chset'=>'utf8mb4',#optional, default charset
 #optional paths for ssl
 'ssl_key'=>NULL,
 'ssl_cert'=>NULL,
 'ssl_ca'=>"",#minimum this is required for ssl connections, if set - ssl connection will try to be established. Example: /path/to/cacert.pem
 );
?>
