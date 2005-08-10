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

if($action == "add") {

  $sql = "select count(*) as count from dns_def where name = '$_POST[name]' and type = '$_POST[type]' and target = '$_POST[target]'";
  $result = mysql_query($sql);
  
  $row = mysql_fetch_array($result);

  if($row[count] != 0) alert("You already have a $_POST[type] record for $_POST[name] pointing to $_POST[target]");
  else {

    if($_POST[name] == $_POST[target] and $_POST[type] != "MX") alert("Your record name and target cannot be the same.");
    else {

      if( ($_POST[type] == "SOA" or $_POST[type] == "MX" or $_POST[type] == "CNAME") and is_ip($_POST[target]) ) alert("A $_POST[type] record cannot point to an IP address!");
      else {
	
	if(ereg('\.$',$_POST[name])) alert("You cannot enter in a full domain as the record name.");
	else {
	  
	  if($_POST[type] == "MX") $_POST[type] .= '-' . $_POST[preference];
	  
	  $sql = "insert into dns_def set name = '$_POST[name]', type = '$_POST[type]', target = '$_POST[target]'";
	  
	  mysql_query($sql);
	  
	  goto("dns_def.php");

	}
	
      }
      
    }
    
  }
  
}

nav_top();

print '<form method=post>
<input type=hidden name=action value=add>
';

switch($_POST[type]) {

 case "SOA":

   $sql = "select count(*) as count from dns_def where type = 'SOA'";
   $result = mysql_query($sql);
   
   $row = mysql_fetch_array($result);
   
   if($row[count] != 0) {

     print 'You already have a default SOA record set';
     nav_bottom();
     exit;

   }

   print '<input type=hidden name=type value=SOA>
Default Start of Authority: <input type=text name=target>
';
   break;
 case "A":
   print '<input type=hidden name=type value=A>
Record Name: <input type=text name=name>
<br>
Target IP: <input type=text name=target>
';
   
   break;
 case "NS":
   print '<input type=hidden name=type value=NS>
<input type=hidden name=name value="@">
Nameserver: <input type=text name=target>
';
   break;
 case "MX":
   print 'Mail for the domain: <input type=hidden name=name value="@">
<input type=hidden name=type value=MX>
MX Preference: <select name=preference>';
   for($i = 10; $i < 51; $i+=10) print '<option value="' . $i . '">' . $i . '</option>';
print '</select>
<br>
Mail Server: <input type=text name=target> ( must not be an IP! )
';
   break;
 case "CNAME":
   print '<input type=hidden name=type value=CNAME>
Alias name: <input type=text name=name>
<br>
Target name: <input type=text name=target>';
   break;
 case "PTR":
   print '<input type=hidden name=type value=PTR>
Reverse pointer records are not yet available';
   nav_bottom();
   exit;

   break;
 default:
   print 'Invalid DNS record type';
   nav_bottom();
   exit;
   break;
}

print '<p><input type=submit value="Add Record">
</form>';

nav_bottom();

?>