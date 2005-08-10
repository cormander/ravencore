<?php
/*
                 RavenCore Hosting Control Panel
               Copyright (C) 2005  Corey Henderson

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

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