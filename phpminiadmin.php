<?php
/*
 PHP Mini MySQL Admin
 (c) 2004-2017 Oleg Savchuk <osalabs@gmail.com> http://osalabs.com

 Light standalone PHP script for quick and easy access MySQL databases.
 http://phpminiadmin.sourceforge.net

 Dual licensed: GPL v2 and MIT, see texts at http://opensource.org/licenses/
*/

$ACCESS_PWD=''; #!!!IMPORTANT!!! this is script access password, SET IT if you want to protect you DB from public access

#DEFAULT db connection settings
# --- WARNING! --- if you set defaults - it's recommended to set $ACCESS_PWD to protect your db!
$DBDEF=array(
'user'=>"",#required
'pwd'=>"", #required
'db'=>"",  #optional, default DB
'host'=>"",#optional
'port'=>"",#optional
'chset'=>"utf8",#optional, default charset
);
$IS_COUNT=false; #set to true if you want to see Total records when pagination occurs (SLOWS down all select queries!)
$DUMP_FILE=dirname(__FILE__).'/pmadump'; #path to file without extension used for server-side exports (timestamp, .sql/.csv/.gz extension added) or imports(.sql)
file_exists($f=dirname(__FILE__) . '/phpminiconfig.php')&&require($f); // Read from config (easier to update)
if (function_exists('date_default_timezone_set')) date_default_timezone_set('UTC');#required by PHP 5.1+

//constants
$VERSION='1.9.170730';
$MAX_ROWS_PER_PAGE=50; #max number of rows in select per one page
$D="\r\n"; #default delimiter for export
$BOM=chr(239).chr(187).chr(191);
$SHOW_D="SHOW DATABASES";
$SHOW_T="SHOW TABLE STATUS";
$DB=array(); #working copy for DB settings

$self=$_SERVER['PHP_SELF'];

session_set_cookie_params(0, null, null, false, true);
session_start();
if (!isset($_SESSION['XSS'])) $_SESSION['XSS']=get_rand_str(16);
$xurl='XSS='.$_SESSION['XSS'];

ini_set('display_errors',0);  #turn on to debug db or script issues
error_reporting(E_ALL ^ E_NOTICE);

//strip quotes if they set
if (get_magic_quotes_gpc()){
  $_COOKIE=array_map('killmq',$_COOKIE);
  $_REQUEST=array_map('killmq',$_REQUEST);
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
  if (!$ACCESS_PWD) {
    $_SESSION['is_logged']=true;
    loadcfg();
  }else{
    print_login();
    exit;
  }
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
$SQLq=trim(b64d($_REQUEST['q']));
$page=$_REQUEST['p']+0;
if ($_REQUEST['refresh'] && $DB['db'] && preg_match('/^show/',$SQLq) ) $SQLq=$SHOW_T;

if (db_connect('nodie')){
  $time_start=microtime_float();

  if ($_REQUEST['pi']){
    ob_start();phpinfo();$html=ob_get_clean();preg_match("/<body[^>]*>(.*?)<\/body>/is",$html,$m);
    $sqldr='<div class="pi">'.$m[1].'</div>';
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
     if ($SQLq)check_xss();
     do_sql($SQLq);#perform non-select SQL only if not refresh (to avoid dangerous delete/drop)
    }
   }else{
    if ( $_REQUEST['refresh'] ){
       check_xss();do_sql($SHOW_D);
    }elseif ($_REQUEST['crdb']){
      check_xss();do_sql('CREATE DATABASE `'.$_REQUEST['new_db'].'`');do_sql($SHOW_D);
    }elseif ( preg_match('/^(?:show\s+(?:databases|status|variables|process)|create\s+database|grant\s+)/i',$SQLq) ){
       check_xss();do_sql($SQLq);
    }else{
       $err_msg="Select Database first";
       if (!$SQLq) do_sql($SHOW_D);
    }
   }
  }
  $time_all=ceil((microtime_float()-$time_start)*10000)/10000;

  print_screen();
}else{
  print_cfg();
}

function do_sql($q){
 global $dbh,$last_sth,$last_sql,$reccount,$out_message,$SQLq,$SHOW_T;
 $SQLq=$q;

 if (!do_multi_sql($q)){
    $out_message="Error: ".mysqli_error($dbh);
 }else{
    if ($last_sth && $last_sql){
       $SQLq=$last_sql;
       if (preg_match("/^select|show|explain|desc/i",$last_sql)) {
          if ($q!=$last_sql) $out_message="Results of the last select displayed:";
          display_select($last_sth,$last_sql);
       } else {
         $reccount=mysqli_affected_rows($dbh);
         $out_message="Done.";
         if (preg_match("/^insert|replace/i",$last_sql)) $out_message.=" Last inserted id=".get_identity();
         if (preg_match("/^drop|truncate/i",$last_sql)) do_sql($SHOW_T);
       }
    }
 }
}

function display_select($sth,$q){
 global $dbh,$DB,$sqldr,$reccount,$is_sht,$xurl,$is_sm;
 $rc=array("o","e");
 $dbn=ue($DB['db']);
 $sqldr='';

 $is_shd=(preg_match('/^show\s+databases/i',$q));
 $is_sht=(preg_match('/^show\s+tables|^SHOW\s+TABLE\s+STATUS/',$q));
 $is_show_crt=(preg_match('/^show\s+create\s+table/i',$q));

 if ($sth===FALSE or $sth===TRUE) return;#check if $sth is not a mysql resource

 $reccount=mysqli_num_rows($sth);
 $fields_num=mysqli_field_count($dbh);

 $w='';
 if ($is_sm) $w='sm ';
 if ($is_sht || $is_shd) {$w='wa';
   $url='?'.$xurl."&db=$dbn";
   $sqldr.="<div class='dot'>
 MySQL Server:
 &#183; <a href='$url&q=".b64u("show variables")."'>Show Configuration Variables</a>
 &#183; <a href='$url&q=".b64u("show status")."'>Show Statistics</a>
 &#183; <a href='$url&q=".b64u("show processlist")."'>Show Processlist</a> ";
   if ($is_shd) $sqldr.="&#183; <label>Create new database: <input type='text' name='new_db' placeholder='type db name here'></label> <input type='submit' name='crdb' value='Create'>";
   $sqldr.="<br>";
   if ($is_sht) $sqldr.="Database: &#183; <a href='$url&q=".b64u("show table status")."'>Show Table Status</a>";
   $sqldr.="</div>";
 }
 if ($is_sht){
   $abtn="<div><input type='submit' value='Export' onclick=\"sht('exp')\">
 <input type='submit' value='Drop' onclick=\"if(ays()){sht('drop')}else{return false}\">
 <input type='submit' value='Truncate' onclick=\"if(ays()){sht('trunc')}else{return false}\">
 <input type='submit' value='Optimize' onclick=\"sht('opt')\">
 <b>selected tables</b></div>";
   $sqldr.=$abtn."<input type='hidden' name='dosht' value=''>";
 }

 $sqldr.="<div><table id='res' class='res $w'>";
 $headers="<tr class='h'>";
 if ($is_sht) $headers.="<td><input type='checkbox' name='cball' value='' onclick='chkall(this)'></td>";
 for($i=0;$i<$fields_num;$i++){
    if ($is_sht && $i>0) break;
    $meta=mysqli_fetch_field($sth);
    $headers.="<th><div>".hs($meta->name)."</div></th>";
 }
 if ($is_shd) $headers.="<th>show create database</th><th>show table status</th><th>show triggers</th>";
 if ($is_sht) $headers.="<th>engine</th><th>~rows</th><th>data size</th><th>index size</th><th>show create table</th><th>explain</th><th>indexes</th><th>export</th><th>drop</th><th>truncate</th><th>optimize</th><th>repair</th><th>comment</th>";
 $headers.="</tr>\n";
 $sqldr.=$headers;
 $swapper=false;
 while($row=mysqli_fetch_row($sth)){
   $sqldr.="<tr class='".$rc[$swp=!$swp]."' onclick='tc(this)'>";
   $v=$row[0];
   if ($is_sht){
     $vq='`'.$v.'`';
     $url='?'.$xurl."&db=$dbn&t=".b64u($v);
     $sqldr.="<td><input type='checkbox' name='cb[]' value=\"".hs($vq)."\"></td>"
     ."<td><a href=\"$url&q=".b64u("select * from $vq")."\">".hs($v)."</a></td>"
     ."<td>".hs($row[1])."</td>"
     ."<td align='right'>".hs($row[4])."</td>"
     ."<td align='right'>".hs($row[6])."</td>"
     ."<td align='right'>".hs($row[8])."</td>"
     ."<td>&#183;<a href=\"$url&q=".b64u("show create table $vq")."\">sct</a></td>"
     ."<td>&#183;<a href=\"$url&q=".b64u("explain $vq")."\">exp</a></td>"
     ."<td>&#183;<a href=\"$url&q=".b64u("show index from $vq")."\">ind</a></td>"
     ."<td>&#183;<a href=\"$url&shex=1&rt=".hs(ue($vq))."\">export</a></td>"
     ."<td>&#183;<a href=\"$url&q=".b64u("drop table $vq")."\" onclick='return ays()'>dr</a></td>"
     ."<td>&#183;<a href=\"$url&q=".b64u("truncate table $vq")."\" onclick='return ays()'>tr</a></td>"
     ."<td>&#183;<a href=\"$url&q=".b64u("optimize table $vq")."\" onclick='return ays()'>opt</a></td>"
     ."<td>&#183;<a href=\"$url&q=".b64u("repair table $vq")."\" onclick='return ays()'>rpr</a></td>"
     ."<td>".hs($row[$fields_num-1])."</td>";
   }elseif ($is_shd){
     $url='?'.$xurl."&db=".ue($v);
     $sqldr.="<td><a href=\"$url&q=".b64u("SHOW TABLE STATUS")."\">".hs($v)."</a></td>"
     ."<td><a href=\"$url&q=".b64u("show create database `$v`")."\">scd</a></td>"
     ."<td><a href=\"$url&q=".b64u("show table status")."\">status</a></td>"
     ."<td><a href=\"$url&q=".b64u("show triggers")."\">trig</a></td>";
   }else{
     for($i=0;$i<$fields_num;$i++){
      $v=$row[$i];
      if (is_null($v)) $v="<i>NULL</i>";
      elseif (preg_match('/[\x00-\x09\x0B\x0C\x0E-\x1F]+/',$v)){#all chars <32, except \n\r(0D0A)
       $vl=strlen($v);$pf='';
       if ($vl>16 && $fields_num>1){#show full dump if just one field
         $v=substr($v, 0, 16);$pf='...';
       }
       $v='BINARY: '.chunk_split(strtoupper(bin2hex($v)),2,' ').$pf;
      }else $v=hs($v);
      if ($is_show_crt) $v="<pre>$v</pre>";
      $sqldr.="<td><div>$v".(!strlen($v)?"<br>":'')."</div></td>";
     }
   }
   $sqldr.="</tr>\n";
 }
 $sqldr.="</table></div>\n".$abtn;
}

function print_header(){
 global $err_msg,$VERSION,$DB,$dbh,$self,$is_sht,$xurl,$SHOW_T;
 $dbn=$DB['db'];
?>
<!DOCTYPE html>
<html>
<head><title>phpMiniAdmin</title>
<meta charset="utf-8">
<style type="text/css">
*{box-sizing:border-box;}
body{font-family:Arial,sans-serif;font-size:80%;padding:0;margin:0}
div{padding:3px}
pre{font-size:125%}
textarea{width:100%}
.nav{text-align:center}
.ft{text-align:right;margin-top:20px;font-size:smaller}
.inv{background-color:#069;color:#FFF}
.inv a{color:#FFF}
table{border-collapse:collapse}
table.res{width:100%}
table.wa{width:auto}
table.res th,table.res td{padding:2px;border:1px solid #fff;vertical-align:top}
table.sm th,table.sm td{max-width:30em}
table.sm th>div,table.sm td>div{max-height:3.5em;overflow:hidden}
table.sm th.lg,table.sm td.lg{max-width:inherit}
table.sm th.lg>div,table.sm td.lg>div{max-height:inherit;overflow:inherit}
table.restr{vertical-align:top}
tr.e{background-color:#CCC}
tr.o{background-color:#EEE}
tr.e:hover, tr.o:hover{background-color:#FF9}
tr.h{background-color:#99C}
tr.s{background-color:#FF9}
.err{color:#F33;font-weight:bold;text-align:center}
.frm{width:400px;border:1px solid #999;background-color:#eee;text-align:left}
.frm label .l{width:100px;float:left}
.dot{border-bottom:1px dotted #000}
.ajax{text-decoration:none;border-bottom: 1px dashed}
.qnav{width:30px}
.sbtn{width:100px}
.clear{clear:both;height:0;display:block}
.pi a{text-decoration:none}
.pi hr{display:none}
.pi img{float:right}
.pi .center{text-align:center}
.pi table{margin:0 auto}
.pi table td, .pi table th{border:1px solid #000000;text-align:left;vertical-align:baseline}
.pi table .e{background-color:#ccccff;font-weight:bold}
.pi table .v{background-color:#cccccc}
</style>

<script type="text/javascript">
var LSK='pma_',LSKX=LSK+'max',LSKM=LSK+'min',qcur=0,LSMAX=32;

function $(i){return document.getElementById(i)}
function frefresh(){
 var F=document.DF;
 F.method='get';
 F.refresh.value="1";
 F.GoSQL.click();
}
function go(p,sql){
 var F=document.DF;
 F.p.value=p;
 if(sql)F.q.value=sql;
 F.GoSQL.click();
}
function ays(){
 return confirm('Are you sure to continue?');
}
function chksql(){
 var F=document.DF,v=F.qraw.value;
 if(/^\s*(?:delete|drop|truncate|alter)/.test(v)) if(!ays())return false;
 if(lschk(1)){
  var lsm=lsmax()+1,ls=localStorage;
  ls[LSK+lsm]=v;
  ls[LSKX]=lsm;
  //keep just last LSMAX queries in log
  if(!ls[LSKM])ls[LSKM]=1;
  var lsmin=parseInt(ls[LSKM]);
  if((lsm-lsmin+1)>LSMAX){
   lsclean(lsmin,lsm-LSMAX);
  }
 }
 return true;
}
function tc(tr){
 if (tr.className=='s'){
  tr.className=tr.classNameX;
 }else{
  tr.classNameX=tr.className;
  tr.className='s';
 }
}
function lschk(skip){
 if (!localStorage || !skip && !localStorage[LSKX]) return false;
 return true;
}
function lsmax(){
 var ls=localStorage;
 if(!lschk() || !ls[LSKX])return 0;
 return parseInt(ls[LSKX]);
}
function lsclean(from,to){
 ls=localStorage;
 for(var i=from;i<=to;i++){
  delete ls[LSK+i];ls[LSKM]=i+1;
 }
}
function q_prev(){
 var ls=localStorage;
 if(!lschk())return;
 qcur--;
 var x=parseInt(ls[LSKM]);
 if(qcur<x)qcur=x;
 $('qraw').value=ls[LSK+qcur];
}
function q_next(){
 var ls=localStorage;
 if(!lschk())return;
 qcur++;
 var x=parseInt(ls[LSKX]);
 if(qcur>x)qcur=x;
 $('qraw').value=ls[LSK+qcur];
}
function after_load(){
 var F=document.DF;
 var p=F['v[pwd]'];
 if (p) p.focus();
 qcur=lsmax();

 F.addEventListener('submit',function(e){
  if(!F.qraw)return;
  if(!chksql()){e.preventDefault();return}
  $('q').value=btoa(encodeURIComponent($('qraw').value).replace(/%([0-9A-F]{2})/g,function(m,p){return String.fromCharCode('0x'+p)}));
 });
 var res=$('res');
 if(res)res.addEventListener('dblclick',function(e){
  if(!$('is_sm').checked)return;
  var el=e.target;
  if(el.tagName!='TD')el=el.parentNode;
  if(el.tagName!='TD')return;
  if(el.className.match(/\b\lg\b/))el.className=el.className.replace(/\blg\b/,' ');
  else el.className+=' lg';
 });
}
function logoff(){
 if(lschk()){
  var ls=localStorage;
  var from=parseInt(ls[LSKM]),to=parseInt(ls[LSKX]);
  for(var i=from;i<=to;i++){
   delete ls[LSK+i];
  }
  delete ls[LSKM];delete ls[LSKX];
 }
}
function cfg_toggle(){
 var e=$('cfg-adv');
 e.style.display=e.style.display=='none'?'':'none';
}
function qtpl(s){
 $('qraw').value=s.replace(/%T/g,'`<?php echo $_REQUEST['t']?b64d($_REQUEST['t']):'tablename'?>`');
}
function smview(){
 if($('is_sm').checked){$('res').className+=' sm'}else{$('res').className = $('res').className.replace(/\bsm\b/,' ')}
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
<form method="post" name="DF" id="DF" action="<?php eo($self)?>" enctype="multipart/form-data">
<input type="hidden" name="XSS" value="<?php eo($_SESSION['XSS'])?>">
<input type="hidden" name="refresh" value="">
<input type="hidden" name="p" value="">

<div class="inv">
<a href="http://phpminiadmin.sourceforge.net/" target="_blank"><b>phpMiniAdmin <?php eo($VERSION)?></b></a>
<?php if ($_SESSION['is_logged'] && $dbh){ ?>
 | <a href="?<?php eo($xurl.'&q='.b64u("show databases"))?>">Databases</a>: <select name="db" onChange="frefresh()"><option value='*'> - select/refresh -</option><option value=''> - show all -</option>
<?php echo get_db_select($dbn)?></select>
<?php if($dbn){ $z=" &#183; <a href='".hs($self."?$xurl&db=".ue($dbn)); ?>
<?php echo $z.'&q='.b64u($SHOW_T)?>'>show tables</a>
<?php echo $z?>&shex=1'>export</a>
<?php echo $z?>&shim=1'>import</a>
<?php } ?>
 | <a href="?showcfg=1">Settings</a>
<?php } ?>
<?php if ($_SESSION['is_logged']){?> | <a href="?<?php eo($xurl)?>&logoff=1" onclick="logoff()">Logoff</a> <?php }?>
 | <a href="?pi=1">phpinfo</a>
</div>

<div class="err"><?php eo($err_msg)?></div>

<?php
}

function print_screen(){
 global $out_message, $SQLq, $err_msg, $reccount, $time_all, $sqldr, $page, $MAX_ROWS_PER_PAGE, $is_limited_sql, $last_count, $is_sm;

 $nav='';
 if ($is_limited_sql && ($page || $reccount>=$MAX_ROWS_PER_PAGE) ){
  $nav="<div class='nav'>".get_nav($page, 10000, $MAX_ROWS_PER_PAGE, "javascript:go(%p%)")."</div>";
 }

 print_header();
?>

<div class="dot" style="padding:3px 20px">
<label for="qraw">SQL-query (or multiple queries separated by ";"):</label>&nbsp;<button type="button" class="qnav" onclick="q_prev()">&lt;</button><button type="button" class="qnav" onclick="q_next()">&gt;</button><br>
<textarea id="qraw" cols="70" rows="10"><?php eo($SQLq)?></textarea><br>
<input type="hidden" name="q" id="q" value="<?php b64e($SQLq);?>">
<input type="submit" name="GoSQL" value="Go" class="sbtn">
<input type="button" name="Clear" value=" Clear " onclick="$('qraw').value='';" style="width:100px">
<?php if(!empty($_REQUEST['db'])){ ?>
<div style="float:right">
<input type="button" value="Select" class="sbtn" onclick="qtpl('SELECT *\nFROM %T\nWHERE 1')">
<input type="button" value="Insert" class="sbtn" onclick="qtpl('INSERT INTO %T (`column`, `column`)\nVALUES (\'value\', \'value\')')">
<input type="button" value="Update" class="sbtn" onclick="qtpl('UPDATE %T\nSET `column`=\'value\'\nWHERE 1=0')">
<input type="button" value="Delete" class="sbtn" onclick="qtpl('DELETE FROM %T\nWHERE 1=0')">
</div><br class="clear">
<?php } ?>
</div>
<div class="dot">
<div style="float:right;padding:0 15px"><label><input type="checkbox" name="is_sm" value="1" id="is_sm" onclick="smview()" <?php eo($is_sm?'checked':'')?>> compact view</label></div>
Records: <b><?php eo($reccount); if(!is_null($last_count) && $reccount<$last_count){eo(' out of '.$last_count);}?></b> in <b><?php eo($time_all)?></b> sec<br>
<b><?php eo($out_message)?></b>
</div>
<?php echo $nav.$sqldr.$nav; ?>
<?php
 print_footer();
}

function print_footer(){
?>
</form>
<div class="ft">&copy; 2004-2017 <a href="http://osalabs.com" target="_blank">Oleg Savchuk</a></div>
</body></html>
<?php
}

function print_login(){
 print_header();
?>
<center>
<h3>Access protected by password</h3>
<div style="width:400px;border:1px solid #999999;background-color:#eeeeee">
<label>Password: <input type="password" name="pwd" value=""></label>
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
<label><div class="l">DB user name:</div><input type="text" name="v[user]" value="<?php eo($DB['user'])?>"></label><br>
<label><div class="l">Password:</div><input type="password" name="v[pwd]" value=""></label><br>
<div style="text-align:right"><a href="#" class="ajax" onclick="cfg_toggle()">advanced settings</a></div>
<div id="cfg-adv" style="display:none;">
<label><div class="l">DB name:</div><input type="text" name="v[db]" value="<?php eo($DB['db'])?>"></label><br>
<label><div class="l">MySQL host:</div><input type="text" name="v[host]" value="<?php eo($DB['host'])?>"></label> <label>port: <input type="text" name="v[port]" value="<?php eo($DB['port'])?>" size="4"></label><br>
<label><div class="l">Charset:</div><select name="v[chset]"><option value="">- default -</option><?php echo chset_select($DB['chset'])?></select></label><br>
<br><label for ="rmb"><input type="checkbox" name="rmb" id="rmb" value="1" checked> Remember in cookies for 30 days or until Logoff</label>
</div>
<center>
<input type="hidden" name="savecfg" value="1">
<input type="submit" value=" Apply "><input type="button" value=" Cancel " onclick="window.location='<?php eo($self)?>'">
</center>
</div>
</center>
<?php
 print_footer();
}


//* utilities
function db_connect($nodie=0){
 global $dbh,$DB,$err_msg;

 if ($DB['port']) {
    $dbh=mysqli_connect($DB['host'],$DB['user'],$DB['pwd'],'',(int)$DB['port']);
 } else {
    $dbh=mysqli_connect($DB['host'],$DB['user'],$DB['pwd']);
 }
 if (!$dbh) {
    $err_msg='Cannot connect to the database because: '.mysqli_connect_error();
    if (!$nodie) die($err_msg);
 }

 if ($dbh && $DB['db']) {
  $res=mysqli_select_db($dbh, $DB['db']);
  if (!$res) {
     $err_msg='Cannot select db because: '.mysqli_error($dbh);
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
 if (!$dbh1 or !mysqli_ping($dbh1)) {
    db_connect($skiperr);
    $dbh1=&$dbh;
 }
 return $dbh1;
}

function db_disconnect(){
 global $dbh;
 mysqli_close($dbh);
}

function dbq($s){
 global $dbh;
 if (is_null($s)) return "NULL";
 return "'".mysqli_real_escape_string($dbh,$s)."'";
}

function db_query($sql, $dbh1=NULL, $skiperr=0, $resmod=MYSQLI_STORE_RESULT){
 $dbh1=db_checkconnect($dbh1, $skiperr);
 $sth=mysqli_query($dbh1, $sql, $resmod);
 if (!$sth && $skiperr) return;
 if (!$sth) die("Error in DB operation:<br>\n".mysqli_error($dbh1)."<br>\n$sql");
 return $sth;
}

function db_array($sql, $dbh1=NULL, $skiperr=0, $isnum=0){#array of rows
 $sth=db_query($sql, $dbh1, $skiperr, MYSQLI_USE_RESULT);
 if (!$sth) return;
 $res=array();
 if ($isnum){
   while($row=mysqli_fetch_row($sth)) $res[]=$row;
 }else{
   while($row=mysqli_fetch_assoc($sth)) $res[]=$row;
 }
 mysqli_free_result($sth);
 return $res;
}

function db_row($sql){
 $sth=db_query($sql);
 return mysqli_fetch_assoc($sth);
}

function db_value($sql,$dbh1=NULL,$skiperr=0){
 $sth=db_query($sql,$dbh1,$skiperr);
 if (!$sth) return;
 $row=mysqli_fetch_row($sth);
 return $row[0];
}

function get_identity($dbh1=NULL){
 $dbh1=db_checkconnect($dbh1);
 return mysqli_insert_id($dbh1);
}

function get_db_select($sel=''){
 global $DB,$SHOW_D;
 if (is_array($_SESSION['sql_sd']) && $_REQUEST['db']!='*'){//check cache
    $arr=$_SESSION['sql_sd'];
 }else{
   $arr=db_array($SHOW_D,NULL,1);
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
   $res.="<option value='".hs($b)."' ".($sel && $sel==$b?'selected':'').">".hs($b)."</option>";
 }
 return $res;
}

function microtime_float(){
 list($usec,$sec)=explode(" ",microtime());
 return ((float)$usec+(float)$sec);
}

/* page nav
 $pg=int($_[0]);     #current page
 $all=int($_[1]);     #total number of items
 $PP=$_[2];      #number if items Per Page
 $ptpl=$_[3];      #page url /ukr/dollar/notes.php?page=    for notes.php
 $show_all=$_[5];           #print Totals?
*/
function get_nav($pg, $all, $PP, $ptpl, $show_all=''){
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
  $res.=" <br>\n";

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
    newcookie("conn[db]",  $v['db'],$tm);
    newcookie("conn[user]",$v['user'],$tm);
    newcookie("conn[pwd]", $v['pwd'],$tm);
    newcookie("conn[host]",$v['host'],$tm);
    newcookie("conn[port]",$v['port'],$tm);
    newcookie("conn[chset]",$v['chset'],$tm);
 }else{
    newcookie("conn[db]",  FALSE,-1);
    newcookie("conn[user]",FALSE,-1);
    newcookie("conn[pwd]", FALSE,-1);
    newcookie("conn[host]",FALSE,-1);
    newcookie("conn[port]",FALSE,-1);
    newcookie("conn[chset]",FALSE,-1);
 }
}

// Allow httponly cookies, or the password is stored plain text in a cookie
function newcookie($n,$v,$e){$x;return setcookie($n,$v,$e,$x,$x,!!$x,!$x);}

//during login only - from cookies or use defaults;
function loadcfg(){
 global $DBDEF;

 if( isset($_COOKIE['conn']) ){
    $_SESSION['DB']=$_COOKIE['conn'];
 }else{
    $_SESSION['DB']=$DBDEF;
 }
 if (!strlen($_SESSION['DB']['chset'])) $_SESSION['DB']['chset']=$DBDEF['chset'];#don't allow empty charset
}

//each time - from session to $DB_*
function loadsess(){
 global $DB, $is_sm;

 $DB=$_SESSION['DB'];

 $rdb=$_REQUEST['db'];
 if ($rdb=='*') $rdb='';
 if ($rdb) {
    $DB['db']=$rdb;
 }
 if($_REQUEST['GoSQL']) $_SESSION['is_sm']=$_REQUEST['is_sm']+0;
 $is_sm=$_SESSION['is_sm']+0;
}

function print_export(){
 global $self,$xurl,$DB,$DUMP_FILE;
 $t=$_REQUEST['rt'];
 $l=($t)?"Table $t":"whole DB";
 print_header();
?>
<center>
<h3>Export <?php eo($l)?></h3>
<div class="frm">
<input type="checkbox" name="s" value="1" checked> Structure<br>
<input type="checkbox" name="d" value="1" checked> Data<br><br>
<div><label><input type="radio" name="et" value="" checked> .sql</label>&nbsp;</div>
<div>
<?php if ($t && !strpos($t,',')){?>
 <label><input type="radio" name="et" value="csv"> .csv (Excel style, data only and for one table only)</label>
<?php }else{?>
<label>&nbsp;( ) .csv</label> <small>(to export as csv - go to 'show tables' and export just ONE table)</small>
<?php }?>
</div>
<br>
<div><label><input type="checkbox" name="sp" value="1"> import has super privileges</label></div>
<div><label><input type="checkbox" name="gz" value="1"> compress as .gz</label></div>
<br>
<input type="hidden" name="doex" value="1">
<input type="hidden" name="rt" value="<?php eo($t)?>">
<input type="submit" value=" Download ">
<input type="submit" name="srv" value=" Dump on Server ">
<input type="button" value=" Cancel " onclick="window.location='<?php eo($self.'?'.$xurl.'&db='.ue($DB['db']))?>'">
<p><small>"Dump on Server" exports to file:<br><?php eo(export_fname($DUMP_FILE).'.sql')?></small></p>
</div>
</center>
<?php
 print_footer();
 exit;
}

function export_fname($f,$ist=false){
 $t=$ist?date('Y-m-d-His'):'YYYY-MM-DD-HHMMSS';
 return $f.$t;
}

function do_export(){
 global $DB,$VERSION,$D,$BOM,$ex_isgz,$ex_issrv,$dbh,$out_message;
 $rt=str_replace('`','',$_REQUEST['rt']);
 $t=explode(",",$rt);
 $th=array_flip($t);
 $ct=count($t);
 $z=db_row("show variables like 'max_allowed_packet'");
 $MAXI=floor($z['Value']*0.8);
 if(!$MAXI)$MAXI=838860;
 $aext='';$ctp='';

 $ex_super=($_REQUEST['sp'])?1:0;
 $ex_isgz=($_REQUEST['gz'])?1:0;
 if ($ex_isgz) {
    $aext='.gz';$ctp='application/x-gzip';
 }
 $ex_issrv=($_REQUEST['srv'])?1:0;

 if ($ct==1&&$_REQUEST['et']=='csv'){
  ex_start('.csv');
  ex_hdr($ctp?$ctp:'text/csv',"$t[0].csv$aext");
  if ($DB['chset']=='utf8') ex_w($BOM);

  $sth=db_query("select * from `$t[0]`",NULL,0,MYSQLI_USE_RESULT);
  $fn=mysqli_field_count($dbh);
  for($i=0;$i<$fn;$i++){
   $m=mysqli_fetch_field($sth);
   ex_w(qstr($m->name).(($i<$fn-1)?",":""));
  }
  ex_w($D);
  while($row=mysqli_fetch_row($sth)) ex_w(to_csv_row($row));
  mysqli_free_result($sth);
 }else{
  ex_start('.sql');
  ex_hdr($ctp?$ctp:'text/plain',"$DB[db]".(($ct==1&&$t[0])?".$t[0]":(($ct>1)?'.'.$ct.'tables':'')).".sql$aext");
  ex_w("-- phpMiniAdmin dump $VERSION$D-- Datetime: ".date('Y-m-d H:i:s')."$D-- Host: $DB[host]$D-- Database: $DB[db]$D$D");
  if ($DB['chset']) ex_w("/*!40030 SET NAMES $DB[chset] */;$D");
  $ex_super && ex_w("/*!40030 SET GLOBAL max_allowed_packet=16777216 */;$D$D");
  ex_w("/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;$D$D");

  $sth=db_query("show full tables from `$DB[db]`");
  while($row=mysqli_fetch_row($sth)){
    if (!$rt||array_key_exists($row[0],$th)) do_export_table($row[0],$row[1],$MAXI);
  }

  ex_w("/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;$D$D");
  ex_w("$D-- phpMiniAdmin dump end$D");
 }
 ex_end();
 if (!$ex_issrv) exit;
 $out_message='Export done successfully';
}

function do_export_table($t='',$tt='',$MAXI=838860){
 global $D,$ex_issrv;
 @set_time_limit(600);

 if($_REQUEST['s']){
  $sth=db_query("show create table `$t`");
  $row=mysqli_fetch_row($sth);
  $ct=preg_replace("/\n\r|\r\n|\n|\r/",$D,$row[1]);
  ex_w("DROP TABLE IF EXISTS `$t`;$D$ct;$D$D");
 }

 if ($_REQUEST['d']&&$tt!='VIEW'){//no dump for views
  $exsql='';
  ex_w("/*!40000 ALTER TABLE `$t` DISABLE KEYS */;$D");
  $sth=db_query("select * from `$t`",NULL,0,MYSQLI_USE_RESULT);
  while($row=mysqli_fetch_row($sth)){
    $values='';
    foreach($row as $v) $values.=(($values)?',':'').dbq($v);
    $exsql.=(($exsql)?',':'')."(".$values.")";
    if (strlen($exsql)>$MAXI) {
       ex_w("INSERT INTO `$t` VALUES $exsql;$D");$exsql='';
    }
  }
  mysqli_free_result($sth);
  if ($exsql) ex_w("INSERT INTO `$t` VALUES $exsql;$D");
  ex_w("/*!40000 ALTER TABLE `$t` ENABLE KEYS */;$D$D");
 }
 if (!$ex_issrv) flush();
}

function ex_hdr($ct,$fn){
 global $ex_issrv;
 if ($ex_issrv) return;
 header("Content-type: $ct");
 header("Content-Disposition: attachment; filename=\"$fn\"");
}
function ex_start($ext){
 global $ex_isgz,$ex_gz,$ex_tmpf,$ex_issrv,$ex_f,$DUMP_FILE;
 if ($ex_isgz){
    $ex_tmpf=($ex_issrv?export_fname($DUMP_FILE,true).$ext:tmp_name()).'.gz';
    if (!($ex_gz=gzopen($ex_tmpf,'wb9'))) die("Error trying to create gz tmp file");
 }else{
    if ($ex_issrv) {
      if (!($ex_f=fopen(export_fname($DUMP_FILE,true).$ext,'wb'))) die("Error trying to create dump file");
    }
 }
}
function ex_w($s){
 global $ex_isgz,$ex_gz,$ex_issrv,$ex_f;
 if ($ex_isgz){
    gzwrite($ex_gz,$s,strlen($s));
 }else{
    if ($ex_issrv){
        fwrite($ex_f,$s);
    }else{
        echo $s;
    }
 }
}
function ex_end(){
 global $ex_isgz,$ex_gz,$ex_tmpf,$ex_issrv,$ex_f;
 if ($ex_isgz){
    gzclose($ex_gz);
    if (!$ex_issrv){
      readfile($ex_tmpf);
      unlink($ex_tmpf);
    }
 }else{
    if ($ex_issrv) fclose($ex_f);
 }
}

function print_import(){
 global $self,$xurl,$DB,$DUMP_FILE;
 print_header();
?>
<center>
<h3>Import DB</h3>
<div class="frm">
<div><label><input type="radio" name="it" value="" checked> import by uploading <b>.sql</b> or <b>.gz</b> file:</label>
 <input type="file" name="file1" value="" size=40><br>
</div>
<div><label><input type="radio" name="it" value="sql"> import from file on server:<br>
 <?php eo($DUMP_FILE.'.sql')?></label></div>
<div><label><input type="radio" name="it" value="gz"> import from file on server:<br>
 <?php eo($DUMP_FILE.'.sql.gz')?></label></div>
<input type="hidden" name="doim" value="1">
<input type="submit" value=" Import " onclick="return ays()"><input type="button" value=" Cancel " onclick="window.location='<?php eo($self.'?'.$xurl.'&db='.ue($DB['db']))?>'">
</div>
<br><br><br>
<!--
<h3>Import one Table from CSV</h3>
<div class="frm">
.csv file (Excel style): <input type="file" name="file2" value="" size=40><br>
<input type="checkbox" name="r1" value="1" checked> first row contain field names<br>
<small>(note: for success, field names should be exactly the same as in DB)</small><br>
Character set of the file: <select name="chset"><?php echo chset_select('utf8')?></select>
<br><br>
Import into:<br>
<input type="radio" name="tt" value="1" checked="checked"> existing table:
 <select name="t">
 <option value=''>- select -</option>
 <?php echo sel(db_array('show tables',NULL,0,1), 0, ''); ?>
</select>
<div style="margin-left:20px">
 <input type="checkbox" name="ttr" value="1"> replace existing DB data<br>
 <input type="checkbox" name="tti" value="1"> ignore duplicate rows
</div>
<input type="radio" name="tt" value="2"> create new table with name <input type="text" name="tn" value="" size="20">
<br><br>
<input type="hidden" name="doimcsv" value="1">
<input type="submit" value=" Upload and Import " onclick="return ays()"><input type="button" value=" Cancel " onclick="window.location='<?php eo($self)?>'">
</div>
-->
</center>
<?php
 print_footer();
 exit;
}

function do_import(){
 global $err_msg,$out_message,$dbh,$SHOW_T,$DUMP_FILE;
 $err_msg='';
 $it=$_REQUEST['it'];

 if (!$it){
    $F=$_FILES['file1'];
    if ($F && $F['name']){
       $filename=$F['tmp_name'];
       $pi=pathinfo($F['name']);
       $ext=$pi['extension'];
    }
 }else{
    $ext=($it=='gz'?'sql.gz':'sql');
    $filename=$DUMP_FILE.'.'.$ext;
 }

 if ($filename && file_exists($filename)){
  if ($ext!='sql'){//if not sql - assume .gz and extract
     $tmpf=tmp_name();
     if (($gz=gzopen($filename,'rb')) && ($tf=fopen($tmpf,'wb'))){
        while(!gzeof($gz)){
           if (fwrite($tf,gzread($gz,8192),8192)===FALSE){$err_msg='Error during gz file extraction to tmp file';break;}
        }//extract to tmp file
        gzclose($gz);fclose($tf);$filename=$tmpf;
     }else{$err_msg='Error opening gz file';}
  }
  if (!$err_msg){
   if (!do_multi_sql('', $filename)){
      $err_msg='Import Error: '.mysqli_error($dbh);
   }else{
      $out_message='Import done successfully';
      do_sql($SHOW_T);
      return;
  }}

 }else{
    $err_msg="Error: Please select file first";
 }
 print_import();
 exit;
}

// multiple SQL statements splitter
function do_multi_sql($insql,$fname=''){
 @set_time_limit(600);

 $sql='';
 $ochar='';
 $is_cmt='';
 $GLOBALS['insql_done']=0;
 while ($str=get_next_chunk($insql,$fname)){
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
 global $last_sth,$last_sql,$MAX_ROWS_PER_PAGE,$page,$is_limited_sql,$last_count,$IS_COUNT;
 $sql=trim($sql);
 $sql=preg_replace("/;$/","",$sql);
 if ($sql){
    $last_sql=$sql;$is_limited_sql=0;
    $last_count=NULL;
    if (preg_match("/^select/i",$sql) && !preg_match("/limit +\d+/i", $sql)){
       if ($IS_COUNT){
          #get total count
          $sql1='select count(*) from ('.$sql.') ___count_table';
          $last_count=db_value($sql1,NULL,'noerr');
       }
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
 global $SHOW_T;
 $cb=$_REQUEST['cb'];
 if (!is_array($cb)) $cb=array();
 $sql='';
 switch ($_REQUEST['dosht']){
  case 'exp':$_REQUEST['t']=join(",",$cb);print_export();exit;
  case 'drop':$sq='DROP TABLE';break;
  case 'trunc':$sq='TRUNCATE TABLE';break;
  case 'opt':$sq='OPTIMIZE TABLE';break;
 }
 if ($sq){
  foreach($cb as $v){
   $sql.=$sq." $v;\n";
  }
 }
 if ($sql) do_sql($sql);
 do_sql($SHOW_T);
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
 $chars=preg_split('//','ABCDEFabcdef0123456789');
 for($i=0;$i<$len;$i++) $result.=$chars[rand(0,count($chars)-1)];
 return $result;
}

function check_xss(){
 global $self;
 if ($_SESSION['XSS']!=trim($_REQUEST['XSS'])){
    unset($_SESSION['XSS']);
    header("location: $self");
    exit;
 }
}

function rw($s){#for debug
 echo hs(var_dump($s))."<br>\n";
}

function tmp_name() {
  if ( function_exists('sys_get_temp_dir')) return tempnam(sys_get_temp_dir(),'pma');

  if( !($temp=getenv('TMP')) )
    if( !($temp=getenv('TEMP')) )
      if( !($temp=getenv('TMPDIR')) ) {
        $temp=tempnam(__FILE__,'');
        if (file_exists($temp)) {
          unlink($temp);
          $temp=dirname($temp);
        }
      }
  return $temp ? tempnam($temp,'pma') : null;
}

function hs($s){
  return htmlspecialchars($s, ENT_COMPAT|ENT_HTML401,'UTF-8');
}
function eo($s){//echo+escape
  echo hs($s);
}
function ue($s){
  return urlencode($s);
}

function b64e($s){
  return base64_encode($s);
}
function b64u($s){
  return ue(base64_encode($s));
}
function b64d($s){
  return base64_decode($s);
}
?>
