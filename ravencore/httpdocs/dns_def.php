<?php

include "auth.php";

req_service("dns");

req_admin();

if($action == "delete") {

  $sql = "delete from dns_def where id = '$_POST[delete]'";
  mysql_query($sql);

  goto("$_SERVER[PHP_SELF]");

}

nav_top();

$sql = "select * from dns_def order by type, name, target";
$result = mysql_query($sql);
    
$num = mysql_num_rows($result);

if($num == 0) print "No default DNS records setup for this server";
else {
      
  print '<h3>Default DNS for domains setup on this server</h3><form method=post><table><tr><td>&nbsp;</td><td>Record Name</td><td>Record Type</td><td>Record Target</td></tr>';
      
  while( $row = mysql_fetch_array($result) ) {
    
    print '<tr><td><input type=radio name=delete value="' . $row[id] . '"></td><td>' . $row[name] . '</td><td>' . $row[type] . '</td><td>' . $row[target] . '</td></tr>';
    
      }
  
  print '<tr><td colspan=4><input type=submit value="Delete Selected"></tr>
<input type=hidden name=action value=delete>
<input type=hidden name=did value="' . $did. '">
</table></form>';
  
}

print '<p><form method=post action="add_def_dns.php">
Add record: <select name=type>
<option value=A>A</option>
<option value=NS>NS</option>
<option value=MX>MX</option>
<option value=SOA>SOA</option>
<option value=CNAME>CNAME</option>
<option value=PTR>PTR</option>
</select> <input type=submit value=Add>
<input type=hidden name=did value="' . $did . '">
</form>';

nav_bottom();

?>