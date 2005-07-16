<?php

include "auth.php";

req_service("web");

if(!$did) goto("users.php?uid=$uid");

$domain_name = get_domain_name($did);

if($action == "add") {

  //quick hack to get all the allowed status codes into an array
  $handle = popen("cat http_status_codes.php | awk '{print \$1}' | grep '^[[:digit:]]'","r");

  while( !feof($handle) ) $code_data .= fread($handle, 1024);

  pclose($handle);

  $http_codes = explode("\n", $code_data);
  
  if(!in_array($_POST[code], $http_codes)) alert("$_POST[code] is not a valid http code!");
  else {

    $sql = "select count(*) as count from error_docs where did = '$did' and code = '$_POST[code]'";
    $result = mysql_query($sql);
    
    $row = mysql_fetch_array($result);
    
    if($row[count] != 0) alert("You already have a $_POST[code] error document");
    else {
      
      $sql = "insert into error_docs set did = '$did', code = '$_POST[code]', file = '$_POST[file]'";
      mysql_query($sql);
      
      socket_cmd("rehash_httpd $domain_name");
      
      goto("error_docs.php?did=$did");
      
    }
    
  }

} else if($action == "delete") {
    
  $sql = "delete from error_docs where did = '$did' and code = '$_POST[code]'";
  mysql_query($sql);
  
  socket_cmd("rehash_httpd $domain_name");

  goto("error_docs.php?did=$did");

}

nav_top();

$sql = "select * from error_docs where did = '$did'";
$result = mysql_query($sql);

$num = mysql_num_rows($result);

if($num == 0) print 'No custom error documents setup.';
else {

  print '<form method=POST>';

  while( $row = mysql_fetch_array($result) ) {
    
    print '<input type=radio name=code value=' . $row[code] . '> ' . $row[code] . ' - ' . $row[file] . '<br>';
    
  }
  
  print '<input type=submit value=Delete><input type=hidden name=action value=delete></form>';

}

print '<p><form method=POST>
Add Custom Error Document:
<br>
Code: <input type=text size=2 name=code>
<br>
File: <input type=text name=file>
<p>
<input type=submit value=Add>
<input type=hidden name=action value=add>
<input type=hidden name=did value=' . $did . '>
</form>';

print '<p><a href="http_status_codes.php" target=_blank>List HTTP Status Codes</a>';

nav_bottom();

?>