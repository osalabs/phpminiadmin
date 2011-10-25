<?php
/*
 PHP Mini MySQL Admin
 (c) 2004-2011 Oleg Savchuk <osalabs@gmail.com> http://osalabs.com

 Light standalone PHP script for easy access MySQL databases.
 http://phpminiadmin.sourceforge.net
*/

 $ACCESS_PWD=''; #!!!IMPORTANT!!! this is script access password, SET IT if you want to protect you DB from public access

 #DEFAULT db connection settings
 # --- WARNING! --- if you set defaults - always recommended to set $ACCESS_PWD to protect your db!
 $DBDEF=array(
 'user'=>"",#required
 'pwd'=>"", #required
 'db'=>"",  #optional, default DB
 'host'=>"",#optional
 'port'=>"",#optional
 'chset'=>"utf8",#optional, default charset
 );
 date_default_timezone_set('UTC');#required by PHP 5.1+

//constants
 $VERSION='1.7.110429';
 $MAX_ROWS_PER_PAGE=50; #max number of rows in select per one page
 $D="\r\n"; #default delimiter for export
 $BOM=chr(239).chr(187).chr(191);
 $DB=array(); #working copy for DB settings

 $self=$_SERVER['PHP_SELF'];

 session_start();
 if (!isset($_SESSION['XSS'])) $_SESSION['XSS']=get_rand_str(16);
 $xurl='XSS='.$_SESSION['XSS'];

 ini_set('display_errors',1);  #TODO turn off before deploy
 error_reporting(E_ALL ^ E_NOTICE);

//strip quotes if they set
 if (get_magic_quotes_gpc()){
  $_COOKIE=array_map('killmq',$_COOKIE);
  $_REQUEST=array_map('killmq',$_REQUEST);
 }

 if (!$ACCESS_PWD) {
    $_SESSION['is_logged']=true;
    loadcfg();
 }

 if ($_REQUEST['login']){
    if ($_REQUEST['pwd']!=$ACCESS_PWD){
       $err_msg="Invalid password. Try again";
    }else{
       $_SESSION['is_logged']=true;
       loadcfg();
    }
 }

 if ($_REQUEST['logoff']){
    check_xss();
    $_SESSION = array();
    savecfg();
    session_destroy();
    $url=$self;
    if (!$ACCESS_PWD) $url='/';
    header("location: $url");
    exit;
 }

 if (!$_SESSION['is_logged']){
    print_login();
    exit;
 }

 if ($_REQUEST['savecfg']){
    check_xss();
    savecfg();
 }

 loadsess();

 if ($_REQUEST['showcfg']){
    print_cfg();
    exit;
 }

 //get initial values
 $SQLq=trim($_REQUEST['q']);
 $page=$_REQUEST['p']+0;
 if ($_REQUEST['refresh'] && $DB['db'] && preg_match('/^show/',$SQLq) ) $SQLq="show tables";

 if (db_connect('nodie')){
    $time_start=microtime_float();
   
    if ($_REQUEST['phpinfo']){
       ob_start();phpinfo();$sqldr='<div style="font-size:130%">'.ob_get_clean().'</div>';
    }else{
     if ($DB['db']){
      if ($_REQUEST['shex']){
       print_export();
      }elseif ($_REQUEST['doex']){
       check_xss();do_export();
      }elseif ($_REQUEST['shim']){
       print_import();
      }elseif ($_REQUEST['doim']){
       check_xss();do_import();
      }elseif ($_REQUEST['dosht']){
       check_xss();do_sht();
      }elseif (!$_REQUEST['refresh'] || preg_match('/^select|show|explain|desc/i',$SQLq) ){
       check_xss();do_sql($SQLq);#perform non-selet SQL only if not refresh (to avoid dangerous delete/drop)
      }
     }else{
        if ( $_REQUEST['refresh'] ){
           check_xss();do_sql('show databases');
        }elseif ( preg_match('/^show\s+(?:databases|status|variables|process)/i',$SQLq) ){
           check_xss();do_sql($SQLq);
        }else{
           $err_msg="Select Database first";
        }
     }
    }
    $time_all=ceil((microtime_float()-$time_start)*10000)/10000;
   
    print_screen();
 }else{
    print_cfg();
 }

function do_sql($q){
 global $dbh,$last_sth,$last_sql,$reccount,$out_message,$SQLq;
 $SQLq=$q;

 if (!do_multi_sql($q,'',1)){
    $out_message="Error: ".mysql_error($dbh);
 }else{
    if ($last_sth && $last_sql){
       $SQLq=$last_sql;
       if (preg_match("/^select|show|explain|desc/i",$last_sql)) {
          if ($q!=$last_sql) $out_message="Results of the last select displayed:";
          display_select($last_sth,$last_sql);
       } else {
         $reccount=mysql_affected_rows($dbh);
         $out_message="Done.";
         if (preg_match("/^insert|replace/i",$last_sql)) $out_message.=" Last inserted id=".get_identity();
         if (preg_match("/^drop|truncate/i",$last_sql)) do_sql("show tables");
       }
    }
 }
}

function display_select($sth,$q){
 global $dbh,$DB,$sqldr,$reccount,$is_sht,$xurl;
 $rc=array("o","e");
 $dbn=$DB['db'];
 $sqldr='';

 $is_shd=(preg_match('/^show\s+databases/i',$q));
 $is_sht=(preg_match('/^show\s+tables/i',$q));
 $is_show_crt=(preg_match('/^show\s+create\s+table/i',$q));

 $reccount=mysql_num_rows($sth);
 $fields_num=mysql_num_fields($sth);
 
 $w="width='100%' ";
 if ($is_sht || $is_shd) {$w='';
   $url='?'.$xurl."&db=$dbn";
   $sqldr.="<div class='dot'>
&nbsp;MySQL Server:
&nbsp;&#183;<a href='$url&q=show+variables'>Show Configuration Variables</a>
&nbsp;&#183;<a href='$url&q=show+status'>Show Statistics</a>
&nbsp;&#183;<a href='$url&q=show+processlist'>Show Processlist</a>
<br/>";
   if ($is_sht) $sqldr.="&nbsp;Database:&nbsp;&#183;<a href='$url&q=show+table+status'>Show status</a>";
   $sqldr.="</div>";
 }
 if ($is_sht){
   $abtn="&nbsp;<input type='submit' value='Export' onclick=\"sht('exp')\">
 <input type='submit' value='Drop' onclick=\"if(ays()){sht('drop')}else{return false}\">
 <input type='submit' value='Truncate' onclick=\"if(ays()){sht('tunc')}else{return false}\">
 <input type='submit' value='Optimize' onclick=\"sht('opt')\">
 <b>selected tables</b>";
   $sqldr.=$abtn."<input type='hidden' name='dosht' value=''>";
 }

 $sqldr.="<table border='0' cellpadding='1' cellspacing='1' $w class='res'>";
 $headers="<tr class='h'>";
 if ($is_sht) $headers.="<td><input type='checkbox' name='cball' value='' onclick='chkall(this)'></td>";
 for($i=0;$i<$fields_num;$i++){
    $meta=mysql_fetch_field($sth,$i);
    $headers.="<th>".$meta->name."</th>";
 }
 if ($is_shd) $headers.="<th>show create database</th><th>show table status</th><th>show triggers</th>";
 if ($is_sht) $headers.="<th>show create table</th><th>explain</th><th>indexes</th><th>export</th><th>drop</th><th>truncate</th><th>optimize</th><th>repair</th>";
 $headers.="</tr>\n";
 $sqldr.=$headers;
 $swapper=false;
 while($row=mysql_fetch_row($sth)){
   $sqldr.="<tr class='".$rc[$swp=!$swp]."' onmouseover='tmv(this)' onmouseout='tmo(this)' onclick='tc(this)'>";
   for($i=0;$i<$fields_num;$i++){
      $v=$row[$i];$more='';
      if ($is_sht && $i==0 && $v){
         $vq='`'.$v.'`';
         $url='?'.$xurl."&db=$dbn";
         $v="<input type='checkbox' name='cb[]' value=\"$vq\"></td>"
         ."<td><a href=\"$url&q=select+*+from+$vq\">$v</a></td>"
         ."<td>&#183;<a href=\"$url&q=show+create+table+$vq\">sct</a></td>"
         ."<td>&#183;<a href=\"$url&q=explain+$vq\">exp</a></td>"
         ."<td>&#183;<a href=\"$url&q=show+index+from+$vq\">ind</a></td>"
         ."<td>&#183;<a href=\"$url&shex=1&t=$vq\">export</a></td>"
         ."<td>&#183;<a href=\"$url&q=drop+table+$vq\" onclick='return ays()'>dr</a></td>"
         ."<td>&#183;<a href=\"$url&q=truncate+table+$vq\" onclick='return ays()'>tr</a></td>"
         ."<td>&#183;<a href=\"$url&q=optimize+table+$vq\" onclick='return ays()'>opt</a></td>"
         ."<td>&#183;<a href=\"$url&q=repair+table+$vq\" onclick='return ays()'>rpr</a>";
      }elseif ($is_shd && $i==0 && $v){
         $url='?'.$xurl."&db=$v";
         $v="<a href=\"$url&q=show+tables\">$v</a></td>"
         ."<td><a href=\"$url&q=show+create+database+`$v`\">sct</a></td>"
         ."<td><a href=\"$url&q=show+table+status\">status</a></td>"
         ."<td><a href=\"$url&q=show+triggers\">trig</a></td>"
         ;
      }else{
       if (is_null($v)) $v="NULL";
       $v=htmlspecialchars($v);
      }
      if ($is_show_crt) $v="<pre>$v</pre>";
      $sqldr.="<td>$v".(!strlen($v)?"<br />":'')."</td>";
   }
   $sqldr.="</tr>\n";
 }
 $sqldr.="</table>\n".$abtn;

}

function print_header(){
 global $err_msg,$VERSION,$DB,$dbh,$self,$is_sht,$xurl;
 $dbn=$DB['db'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head><title>phpMiniAdmin</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style type="text/css">
body{font-family:Arial,Helvetica,sans-serif;font-size:80%;padding:0px;margin:0px}
th,td{padding:0px;margin:0px}
div{padding:3px}
pre{font-size:125%}
.inv{background-color:#006699;color:#FFFFFF}
.inv a{color:#FFFFFF}
table.res th, table.res td{padding:2px}
table.res tr{vertical-align:top}
tr.e{background-color:#CCCCCC}
tr.o{background-color:#EEEEEE}
tr.h{background-color:#9999CC}
tr.s{background-color:#FFFF99}
.err{color:#FF3333;font-weight:bold;text-align:center}
.frm{width:400px;border:1px solid #999999;background-color:#eeeeee;text-align:left}
.dot{border-bottom:1px dotted #000}
</style>

<script type="text/javascript">
function $(i){return document.getElementById(i)}
function frefresh(){
 var F=document.DF;
 F.method='get';
 F.refresh.value="1";
 F.submit();
}
function go(p,sql){
 var F=document.DF;
 F.p.value=p;
 if(sql)F.q.value=sql;
 F.submit();
}
function ays(){
 return confirm('Are you sure to continue?');
}
function chksql(){
 var F=document.DF;
 if(/^\s*(?:delete|drop|truncate|alter)/.test(F.q.value)) return ays();
}
function tmv(tr){
 tr.sc=tr.className;
 tr.className='h';
}
function tmo(tr){
 tr.className=tr.sc;
}
function tc(tr){
 tr.className='s';
 tr.sc='s';
}
function after_load(){
}
<?php if($is_sht){?>
function chkall(cab){
 var e=document.DF.elements;
 if (e!=null){
  var cl=e.length;                   
  for (i=0;i<cl;i++){var m=e[i];if(m.checked!=null && m.type=="checkbox"){m.checked=cab.checked}}
 }
}
function sht(f){
 document.DF.dosht.value=f;
}
<?php }?>
</script>

</head>
<body onload="after_load()">
<form method="post" name="DF" action="<?php echo $self?>" enctype="multipart/form-data">
<input type="hidden" name="XSS" value="<?php echo $_SESSION['XSS']?>">
<input type="hidden" name="refresh" value="">
<input type="hidden" name="p" value="">

<div class="inv">
<a href="http://phpminiadmin.sourceforge.net/" target="_blank"><b>phpMiniAdmin <?php echo $VERSION?></b></a>
<?php if ($_SESSION['is_logged'] && $dbh){ ?>
 | 
<a href="?<?php echo $xurl?>&q=show+databases">Databases</a>: <select name="db" onChange="frefresh()"><option value='*'> - select/refresh -</option><option value=''> - show all -</option><?php echo get_db_select($dbn)?></select>
<?php if($dbn){ $z=" &#183;<a href='$self?$xurl&db=$dbn"; ?>
<?php echo $z?>&q=show+tables'>show tables</a>
<?php echo $z?>&q=show+table+status'>status</a>
<?php echo $z?>&shex=1'>export</a>
<?php echo $z?>&shim=1'>import</a>
<?php } ?>
 | <a href="?showcfg=1">Settings</a> 
<?php } ?>
<?php if ($GLOBALS['ACCESS_PWD']){?> | <a href="?<?php echo $xurl?>&logoff=1">Logoff</a> <?php }?>
 | <a href="?phpinfo=1">phpinfo</a>
</div>

<div class="err"><?php echo $err_msg?></div>

<?php
}

function print_screen(){
 global $out_message, $SQLq, $err_msg, $reccount, $time_all, $sqldr, $page, $MAX_ROWS_PER_PAGE, $is_limited_sql;

 print_header();

?>

<div class="dot" style="padding:0 0 5px 20px">
SQL-query (or many queries separated by ";"):<br />
<textarea name="q" cols="70" rows="10" style="width:98%"><?php echo $SQLq?></textarea><br/>
<input type=submit name="GoSQL" value="Go" onclick="return chksql()" style="width:100px">&nbsp;&nbsp;
<input type=button name="Clear" value=" Clear " onClick="document.DF.q.value=''" style="width:100px">
</div>

<div class="dot" style="padding:5px 0 5px 20px">
Records: <b><?php echo $reccount?></b> in <b><?php echo $time_all?></b> sec<br />
<b><?php echo $out_message?></b>
</div>
<div class="sqldr">
<?php
 if ($is_limited_sql && ($page || $reccount>=$MAX_ROWS_PER_PAGE) ){
  echo "<center>".make_List_Navigation($page, 10000, $MAX_ROWS_PER_PAGE, "javascript:go(%p%)")."</center>";
 }
#$reccount

 echo $sqldr?>
</div>
<?php
 print_footer();
}

function print_footer(){
?>
</form>
<br/>
<br/>

<div align="right">
<small>&copy; 2004-2011 <a href="http://osalabs.com" target="_blank">Oleg Savchuk</a></small>
</div>
</body></html>
<?php
}

function print_login(){
 print_header();
?>
<center>
<h3>Access protected by password</h3>
<div style="width:400px;border:1px solid #999999;background-color:#eeeeee">
Password: <input type="password" name="pwd" value="">
<input type="hidden" name="login" value="1">
<input type="submit" value=" Login ">
</div>
</center>
<?php
 print_footer();
}


function print_cfg(){
 global $DB,$err_msg,$self;
 print_header();
?>
<center>
<h3>DB Connection Settings</h3>
<div class="frm">
User name: <input type="text" name="v[user]" value="<?php echo $DB['user']?>"><br />
Password: <input type="password" name="v[pwd]" value=""><br />
MySQL host: <input type="text" name="v[host]" value="<?php echo $DB['host']?>"> port: <input type="text" name="v[port]" value="<?php echo $DB['port']?>" size="4"><br />
DB name: <input type="text" name="v[db]" value="<?php echo $DB['db']?>"><br />
Charset: <select name="v[chset]"><option value="">- default -</option><?php echo chset_select($DB['chset'])?></select><br />
<input type="checkbox" name="rmb" value="1" checked> Remember in cookies for 30 days
<input type="hidden" name="savecfg" value="1">
<input type="submit" value=" Apply "><input type="button" value=" Cancel " onclick="window.location='<?php echo $self?>'">
</div>
</center>
<?php
 print_footer();
}


//* utilities
function db_connect($nodie=0){
 global $dbh,$DB,$err_msg;

 $dbh=@mysql_connect($DB['host'].($DB['port']?":$DB[port]":''),$DB['user'],$DB['pwd']);
 if (!$dbh) {
    $err_msg='Cannot connect to the database because: '.mysql_error();
    if (!$nodie) die($err_msg);
 }

 if ($dbh && $DB['db']) {
  $res=mysql_select_db($DB['db'], $dbh);
  if (!$res) {
     $err_msg='Cannot select db because: '.mysql_error();
     if (!$nodie) die($err_msg);
  }else{
     if ($DB['chset']) db_query("SET NAMES ".$DB['chset']);
  }
 }

 return $dbh;
}

function db_checkconnect($dbh1=NULL, $skiperr=0){
 global $dbh;
 if (!$dbh1) $dbh1=&$dbh;
 if (!$dbh1 or !mysql_ping($dbh1)) {
    db_connect($skiperr);
    $dbh1=&$dbh;
 }
 return $dbh1;
}

function db_disconnect(){
 global $dbh;
 mysql_close($dbh);
}

function dbq($s){
 global $dbh;
 if (is_null($s)) return "NULL";
 return "'".mysql_real_escape_string($s,$dbh)."'";
}

function db_query($sql, $dbh1=NULL, $skiperr=0){
 $dbh1=db_checkconnect($dbh1, $skiperr);
 $sth=@mysql_query($sql, $dbh1);
 if (!$sth && $skiperr) return;
 catch_db_err($dbh1, $sth, $sql);
 return $sth;
}

function db_array($sql, $dbh1=NULL, $skiperr=0, $isnum=0){#array of rows
 $sth=db_query($sql, $dbh1, $skiperr);
 if (!$sth) return;
 $res=array();
 if ($isnum){
   while($row=mysql_fetch_row($sth)) $res[]=$row;
 }else{
   while($row=mysql_fetch_assoc($sth)) $res[]=$row;
 }
 return $res;
}

function catch_db_err($dbh, $sth, $sql=""){
 if (!$sth) die("Error in DB operation:<br/>\n".mysql_error($dbh)."<br/>\n$sql");
}

function get_identity($dbh1=NULL){
 $dbh1=db_checkconnect($dbh1);
 return mysql_insert_id($dbh1);
}

function get_db_select($sel=''){
 global $DB;
 if (is_array($_SESSION['sql_sd']) && $_REQUEST['db']!='*'){//check cache
    $arr=$_SESSION['sql_sd'];
 }else{
   $arr=db_array("show databases",NULL,1);
   if (!is_array($arr)){
      $arr=array( 0 => array('Database' => $DB['db']) );
    }
   $_SESSION['sql_sd']=$arr;
 }
 return @sel($arr,'Database',$sel);
}

function chset_select($sel=''){
 global $DBDEF;
 $result='';
 if ($_SESSION['sql_chset']){
    $arr=$_SESSION['sql_chset'];
 }else{
   $arr=db_array("show character set",NULL,1);
   if (!is_array($arr)) $arr=array(array('Charset'=>$DBDEF['chset']));
   $_SESSION['sql_chset']=$arr;
 }

 return @sel($arr,'Charset',$sel);
}

function sel($arr,$n,$sel=''){
 foreach($arr as $a){
#   echo $a[0];
   $b=$a[$n];
   $res.="<option value='$b' ".($sel && $sel==$b?'selected':'').">$b</option>";
 }
 return $res;
}

function microtime_float(){
 list($usec,$sec)=explode(" ",microtime()); 
 return ((float)$usec+(float)$sec); 
} 

############################
# $pg=int($_[0]);     #current page
# $all=int($_[1]);     #total number of items
# $PP=$_[2];      #number if items Per Page
# $ptpl=$_[3];      #page url /ukr/dollar/notes.php?page=    for notes.php
# $show_all=$_[5];           #print Totals?
function make_List_Navigation($pg, $all, $PP, $ptpl, $show_all=''){
  $n='&nbsp;';
  $sep=" $n|$n\n";
  if (!$PP) $PP=10;
  $allp=floor($all/$PP+0.999999);

  $pname='';
  $res='';
  $w=array('Less','More','Back','Next','First','Total');

  $sp=$pg-2;
  if($sp<0) $sp=0;
  if($allp-$sp<5 && $allp>=5) $sp=$allp-5;

  $res="";

  if($sp>0){
    $pname=pen($sp-1,$ptpl);
    $res.="<a href='$pname'>$w[0]</a>";       
    $res.=$sep;
  }
  for($p_p=$sp;$p_p<$allp && $p_p<$sp+5;$p_p++){
     $first_s=$p_p*$PP+1;
     $last_s=($p_p+1)*$PP;
     $pname=pen($p_p,$ptpl);
     if($last_s>$all){
       $last_s=$all;
     }      
     if($p_p==$pg){
        $res.="<b>$first_s..$last_s</b>";
     }else{
        $res.="<a href='$pname'>$first_s..$last_s</a>";
     }       
     if($p_p+1<$allp) $res.=$sep;
  }
  if($sp+5<$allp){
    $pname=pen($sp+5,$ptpl);
    $res.="<a href='$pname'>$w[1]</a>";       
  }
  $res.=" <br/>\n";

  if($pg>0){
    $pname=pen($pg-1,$ptpl);
    $res.="<a href='$pname'>$w[2]</a> $n|$n ";
    $pname=pen(0,$ptpl);
    $res.="<a href='$pname'>$w[4]</a>";   
  }
  if($pg>0 && $pg+1<$allp) $res.=$sep;
  if($pg+1<$allp){
    $pname=pen($pg+1,$ptpl);
    $res.="<a href='$pname'>$w[3]</a>";    
  }    
  if ($show_all) $res.=" <b>($w[5] - $all)</b> ";

  return $res;
}

function pen($p,$np=''){
 return str_replace('%p%',$p, $np);
}

function killmq($value){
 return is_array($value)?array_map('killmq',$value):stripslashes($value);
}

function savecfg(){
 $v=$_REQUEST['v'];
 $_SESSION['DB']=$v;
 unset($_SESSION['sql_sd']);

 if ($_REQUEST['rmb']){
    $tm=time()+60*60*24*30;
    setcookie("conn[db]",  $v['db'],$tm);
    setcookie("conn[user]",$v['user'],$tm);
    setcookie("conn[pwd]", $v['pwd'],$tm);
    setcookie("conn[host]",$v['host'],$tm);
    setcookie("conn[port]",$v['port'],$tm);
    setcookie("conn[chset]",$v['chset'],$tm);
 }else{
    setcookie("conn[db]",  FALSE,-1);
    setcookie("conn[user]",FALSE,-1);
    setcookie("conn[pwd]", FALSE,-1);
    setcookie("conn[host]",FALSE,-1);
    setcookie("conn[port]",FALSE,-1);
    setcookie("conn[chset]",FALSE,-1);
 }
}

//during login only - from cookies or use defaults;
function loadcfg(){
 global $DBDEF;

 if( isset($_COOKIE['conn']) ){
    $a=$_COOKIE['conn'];
    $_SESSION['DB']=$_COOKIE['conn'];
 }else{
    $_SESSION['DB']=$DBDEF;
 }
 if (!strlen($_SESSION['DB']['chset'])) $_SESSION['DB']['chset']=$DBDEF['chset'];#don't allow empty charset
}

//each time - from session to $DB_*
function loadsess(){
 global $DB;

 $DB=$_SESSION['DB'];

 $rdb=$_REQUEST['db'];
 if ($rdb=='*') $rdb='';
 if ($rdb) {
    $DB['db']=$rdb;
 }
}

function print_export(){
 global $self,$xurl,$DB;
 $t=$_REQUEST['t'];
 $l=($t)?"Table $t":"whole DB";
 print_header();
?>
<center>
<h3>Export <?php echo $l?></h3>
<div class="frm">
<input type="checkbox" name="s" value="1" checked> Structure<br />
<input type="checkbox" name="d" value="1" checked> Data<br /><br />
<label><input type="radio" name="et" value="" checked> .sql</label><br />
<?php if ($t && !strpos($t,',')){?>
 <label><input type="radio" name="et" value="csv"> .csv (Excel style, data only and for one table only)</label>
<?php }else{?>
&nbsp;( ) .csv <small>(to export as csv - go to 'show tables' and export just ONE table)</small>
<?php }?>
<br /><br />
<label><input type="checkbox" name="gz" value="1"> compress as .gz</label><br />
<br />
<input type="hidden" name="doex" value="1">
<input type="hidden" name="t" value="<?php echo $t?>">
<input type="submit" value=" Download "><input type="button" value=" Cancel " onclick="window.location='<?php echo $self.'?'.$xurl.'&db='.$DB['db']?>'">
</div>
</center>
<?php
 print_footer();
 exit;
}

function do_export(){
 global $DB,$VERSION,$D,$BOM,$ex_isgz;
 $rt=str_replace('`','',$_REQUEST['t']);
 $t=explode(",",$rt);
 $th=array_flip($t);
 $ct=count($t);
 $z=db_array("show variables like 'max_allowed_packet'");
 $MAXI=floor($z[0]['Value']*0.8);
 if(!$MAXI)$MAXI=838860;
 $aext='';$ctp='';

 $ex_isgz=($_REQUEST['gz'])?1:0;
 if ($ex_isgz) {
    $aext='.gz';$ctp='application/x-gzip';
 }
 ex_start();

 if ($ct==1&&$_REQUEST['et']=='csv'){
  ex_hdr($ctp?$ctp:'text/csv',"$t[0].csv$aext");
  if ($DB['chset']=='utf8') ex_end($BOM);

  $sth=db_query("select * from `$t[0]`");
  $fn=mysql_num_fields($sth);
  for($i=0;$i<$fn;$i++){
   $m=mysql_fetch_field($sth,$i);
   ex_w(qstr($m->name).(($i<$fn-1)?",":""));
  }
  ex_w($D);
  while($row=mysql_fetch_row($sth)) ex_w(to_csv_row($row));
  ex_end();
  exit;
 }

 ex_hdr($ctp?$ctp:'text/plain',"$DB[db]".(($ct==1&&$t[0])?".$t[0]":(($ct>1)?'.'.$ct.'tables':'')).".sql$aext");
 ex_w("-- phpMiniAdmin dump $VERSION$D-- Datetime: ".date('Y-m-d H:i:s')."$D-- Host: $DB[host]$D-- Database: $DB[db]$D$D");
 ex_w("/*!40030 SET NAMES $DB[chset] */;$D/*!40030 SET GLOBAL max_allowed_packet=16777216 */;$D$D");

 $sth=db_query("show tables from `$DB[db]`");
 while($row=mysql_fetch_row($sth)){
   if (!$rt||array_key_exists($row[0],$th)) do_export_table($row[0],1,$MAXI);
 }

 ex_w("$D-- phpMiniAdmin dump end$D");
 ex_end();
 exit;
}

function do_export_table($t='',$isvar=0,$MAXI=838860){
 global $D;
 set_time_limit(600);

 if($_REQUEST['s']){
  $sth=db_query("show create table `$t`");
  $row=mysql_fetch_row($sth);
  $ct=preg_replace("/\n\r|\r\n|\n|\r/",$D,$row[1]);
  ex_w("DROP TABLE IF EXISTS `$t`;$D$ct;$D$D");
 }

 if ($_REQUEST['d']){
  $exsql='';
  ex_w("/*!40000 ALTER TABLE `$t` DISABLE KEYS */;$D");
  $sth=db_query("select * from `$t`");
  while($row=mysql_fetch_row($sth)){
    $values='';
    foreach($row as $v) $values.=(($values)?',':'').dbq($v);
    $exsql.=(($exsql)?',':'')."(".$values.")";
    if (strlen($exsql)>$MAXI) {
       ex_w("INSERT INTO `$t` VALUES $exsql;$D");$exsql='';
    }
  }
  if ($exsql) ex_w("INSERT INTO `$t` VALUES $exsql;$D");
  ex_w("/*!40000 ALTER TABLE `$t` ENABLE KEYS */;$D$D");
 }
 flush();
}

function ex_hdr($ct,$fn){
 header("Content-type: $ct");
 header("Content-Disposition: attachment; filename=\"$fn\"");
}
function ex_start(){
 global $ex_isgz,$ex_gz,$ex_tmpf;
 if ($ex_isgz){
    $ex_tmpf=tempnam(sys_get_temp_dir(),'pma').'.gz';
    if (!($ex_gz=gzopen($ex_tmpf,'wb9'))) die("Error trying to create gz tmp file");
 }
}
function ex_w($s){
 global $ex_isgz,$ex_gz;
 if ($ex_isgz){
    gzwrite($ex_gz,$s,strlen($s));
 }else{
    echo $s;
 }
}
function ex_end(){
 global $ex_isgz,$ex_gz,$ex_tmpf;
 if ($ex_isgz){
    gzclose($ex_gz);
    readfile($ex_tmpf);
 }
}

function print_import(){
 global $self,$xurl,$DB;
 print_header();
?>
<center>
<h3>Import DB</h3>
<div class="frm">
<b>.sql</b> or <b>.gz</b> file: <input type="file" name="file1" value="" size=40><br />
<input type="hidden" name="doim" value="1">
<input type="submit" value=" Upload and Import " onclick="return ays()"><input type="button" value=" Cancel " onclick="window.location='<?php echo $self.'?'.$xurl.'&db='.$DB['db']?>'">
</div>
<br /><br /><br />
<!--
<h3>Import one Table from CSV</h3>
<div class="frm">
.csv file (Excel style): <input type="file" name="file2" value="" size=40><br />
<input type="checkbox" name="r1" value="1" checked> first row contain field names<br/>
<small>(note: for success, field names should be exactly the same as in DB)</small><br />
Character set of the file: <select name="chset"><?php echo chset_select('utf8')?></select>
<br/><br/>
Import into:<br/>
<input type="radio" name="tt" value="1" checked="checked"> existing table:
 <select name="t">
 <option value=''>- select -</option>
 <?php echo sel(db_array('show tables',NULL,0,1), 0, ''); ?>
</select>
<div style="margin-left:20px">
 <input type="checkbox" name="ttr" value="1"> replace existing DB data<br />
 <input type="checkbox" name="tti" value="1"> ignore duplicate rows
</div>
<input type="radio" name="tt" value="2"> create new table with name <input type="text" name="tn" value="" size="20">
<br /><br />
<input type="hidden" name="doimcsv" value="1">
<input type="submit" value=" Upload and Import " onclick="return ays()"><input type="button" value=" Cancel " onclick="window.location='<?php echo $self?>'">
</div>
-->
</center>
<?php
 print_footer();
 exit;
}

function do_import(){
 global $err_msg,$out_message,$dbh;
 $err_msg='';
 $F=$_FILES['file1'];

 if ($F && $F['name']){
  $filename=$F['tmp_name'];
  $pi=pathinfo($F['name']);
  if ($pi['extension']!='sql'){//if not sql - assume .gz
     $tmpf=tempnam(sys_get_temp_dir(),'pma');
     if (($gz=gzopen($filename,'rb')) && ($tf=fopen($tmpf,'wb'))){
        while(!gzeof($gz)){
           if (fwrite($tf,gzread($gz,8192),8192)===FALSE){$err_msg='Error during gz file extraction to tmp file';break;}
        }//extract to tmp file
        gzclose($gz);fclose($tf);$filename=$tmpf;
     }else{$err_msg='Error opening gz file';}
  }
  if (!$err_msg){
   if (!do_multi_sql('', $filename)){
      $err_msg='Import Error: '.mysql_error($dbh);
   }else{
      $out_message='Import done successfully';
      do_sql('show tables');
      return;
  }}
 }else{
  $err_msg="Error: Please select file first";
 }
 print_import();
 exit;
}

// multiple SQL statements splitter
function do_multi_sql($insql, $fname){
 set_time_limit(600);

 $sql='';
 $ochar='';
 $is_cmt='';
 $GLOBALS['insql_done']=0;
 while ( $str=get_next_chunk($insql, $fname) ){
    $opos=-strlen($ochar);
    $cur_pos=0;
    $i=strlen($str);
    while ($i--){
       if ($ochar){
          list($clchar, $clpos)=get_close_char($str, $opos+strlen($ochar), $ochar);
          if ( $clchar ) {
             if ($ochar=='--' || $ochar=='#' || $is_cmt ){
                $sql.=substr($str, $cur_pos, $opos-$cur_pos );
             }else{
                $sql.=substr($str, $cur_pos, $clpos+strlen($clchar)-$cur_pos );
             }
             $cur_pos=$clpos+strlen($clchar);
             $ochar='';
             $opos=0;
          }else{
             $sql.=substr($str, $cur_pos);
             break;
          }
       }else{
          list($ochar, $opos)=get_open_char($str, $cur_pos);
          if ($ochar==';'){
             $sql.=substr($str, $cur_pos, $opos-$cur_pos+1);
             if (!do_one_sql($sql)) return 0;
             $sql='';
             $cur_pos=$opos+strlen($ochar);
             $ochar='';
             $opos=0;
          }elseif(!$ochar) {
             $sql.=substr($str, $cur_pos);
             break;
          }else{
             $is_cmt=0;if ($ochar=='/*' && substr($str, $opos, 3)!='/*!') $is_cmt=1;
          }
       }
    }
 }

 if ($sql){
    if (!do_one_sql($sql)) return 0;
    $sql='';
 }

 return 1;
}

//read from insql var or file
function get_next_chunk($insql, $fname){
 global $LFILE, $insql_done;
 if ($insql) {
    if ($insql_done){
       return '';
    }else{
       $insql_done=1;
       return $insql;
    }
 }
 if (!$fname) return '';
 if (!$LFILE){
    $LFILE=fopen($fname,"r+b") or die("Can't open [$fname] file $!");
 }
 return fread($LFILE, 64*1024);
}

function get_open_char($str, $pos){
 if ( preg_match("/(\/\*|^--|(?<=\s)--|#|'|\"|;)/", $str, $m, PREG_OFFSET_CAPTURE, $pos) ) {
    $ochar=$m[1][0];
    $opos=$m[1][1];
 }
 return array($ochar, $opos);
}

#RECURSIVE!
function get_close_char($str, $pos, $ochar){
 $aCLOSE=array(
   '\'' => '(?<!\\\\)\'|(\\\\+)\'',
   '"' => '(?<!\\\\)"',
   '/*' => '\*\/',
   '#' => '[\r\n]+',
   '--' => '[\r\n]+',
 );
 if ( $aCLOSE[$ochar] && preg_match("/(".$aCLOSE[$ochar].")/", $str, $m, PREG_OFFSET_CAPTURE, $pos ) ) {
    $clchar=$m[1][0];
    $clpos=$m[1][1];
    $sl=strlen($m[2][0]);
    if ($ochar=="'" && $sl){
       if ($sl % 2){ #don't count as CLOSE char if number of slashes before ' ODD
          list($clchar, $clpos)=get_close_char($str, $clpos+strlen($clchar), $ochar);
       }else{
          $clpos+=strlen($clchar)-1;$clchar="'";#correction
       }
    }
 }
 return array($clchar, $clpos);
}

function do_one_sql($sql){
 global $last_sth,$last_sql,$MAX_ROWS_PER_PAGE,$page,$is_limited_sql;
 $sql=trim($sql);
 $sql=preg_replace("/;$/","",$sql);
 if ($sql){
    $last_sql=$sql;$is_limited_sql=0;
    if (preg_match("/^select/i",$sql) && !preg_match("/limit +\d+/i", $sql)){
       $offset=$page*$MAX_ROWS_PER_PAGE;
       $sql.=" LIMIT $offset,$MAX_ROWS_PER_PAGE";
       $is_limited_sql=1;
    }
    $last_sth=db_query($sql,0,'noerr');
    return $last_sth;
 }
 return 1;
}

function do_sht(){
 $cb=$_REQUEST['cb'];
 switch ($_REQUEST['dosht']){
  case 'exp':$_REQUEST['t']=join(",",$cb);print_export();exit;
  case 'drop':$sq='DROP TABLE';break;
  case 'trunc':$sq='TRUNCATE TABLE';break;
  case 'opt':$sq='OPTIMIZE TABLE';break;
 }
 if ($sq && is_array($cb)){
  foreach($cb as $v){
   $sql.=$sq." $v;\n";
  }
  do_sql($sql);
 }
 do_sql('show tables');
}

function to_csv_row($adata){
 global $D;
 $r='';
 foreach ($adata as $a){
   $r.=(($r)?",":"").qstr($a);
 }
 return $r.$D;
}
function qstr($s){
 $s=nl2br($s);
 $s=str_replace('"','""',$s);
 return '"'.$s.'"';
}

function get_rand_str($len){
 $result='';
 $chars=array("A","B","C","D","E","F","a","b","c","d","e","f",0,1,2,3,4,5,6,7,8,9);
 for($i=0;$i<$len;$i++) $result.=$chars[rand(0,count($chars))];
 return $result;
}

function check_xss(){
 global $self;
 if ($_SESSION['XSS']!=trim($_REQUEST['XSS'])){
  echo "XSS error. <a href='$self'>relogin to ppm</a>";
  exit;
 }
}

function rw($s){#for debug
 echo $s."<br>\n";
}

?>