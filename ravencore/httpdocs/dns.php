<?php

include "auth.php";

req_service("dns");

if($action == "delete") {

  if($_POST[type] == "SOA") $sql = "update domains set soa = NULL where id = '$did'";
  else  $sql = "delete from dns_rec where did = '$did' and id = '$_POST[delete]'";
  mysql_query($sql);

  socket_cmd("rehash_named --rebuild-conf --all");

  goto("dns.php?did=$did");

}

if(!$did) {

  req_admin();

  nav_top();

  $sql = "select * from domains where soa is not null and soa != ''";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0) print 'No DNS records setup on the server';
  else {

    print 'The following domains are setup for DNS<p>
<table><tr><th>Domain</th><th>Records</th></tr>';

    while( $row = mysql_fetch_array($result) ) {

      print '<tr><td><a href="dns.php?did=' . $row[id] . '">' . $row[name] . '</a></td>';

      $sql = "select count(*) as count from dns_rec where did = '$row[id]'";
      $result_rec = mysql_query($sql);

      $row = mysql_fetch_array($result_rec);

      print '<td align=center>' . $row[count]. '</td></tr>';

    }

    print '</table>';

  }

} else {

  nav_top();

  $sql = "select * from domains where id = '$did' and soa is not null";
  $result = mysql_query($sql);
  
  $num = mysql_num_rows($result);

  if($num == 0) print '<form method=post action=add_dns.php name=main>
No SOA record setup for this domain - <a href="javascript:document.main.submit();">Add SOA record</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=type value="SOA">
</form>';
  else {
  
    $row = mysql_fetch_array($result);
    
    $domain_name = $row[name];  
    
    print 'DNS for <a href="domains.php?did=' . $did . '">' . $domain_name . '</a><p>';
    
    print '<form method=post name=del>
Start of Authority for ' . $domain_name . ' is ' . $row[soa] . ' - <a href="javascript:document.del.submit();">delete</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=type value="SOA">
<input type=hidden name=action value=delete>
</form>
<p>';
    
    $sql = "select * from dns_rec where did = '$did' order by type, name, target";
    $result = mysql_query($sql);
    
    $num = mysql_num_rows($result);
    
    if($num == 0) print "No DNS records setup for this domain";
    else {

      print '<form method=post>';

      print '<table><tr><th>&nbsp;</th><th>Record Name</th><th>Record Type</th><th>Record Target</th></tr>';
      
      while( $row = mysql_fetch_array($result) ) {
	
	print '<tr><td><input type=radio name=delete value="' . $row[id] . '"></td><td>' . $row[name] . '</td><td>' . $row[type] . '</td><td>' . $row[target] . '</td></tr>';
	
      }
      
      print '<tr><td colspan=4><input type=submit value="Delete Selected"></tr>';
      
      print '<input type=hidden name=action value=delete>
<input type=hidden name=did value="' . $did. '">
</table></form>';
      
    }
    
    if(user_can_add($uid,"dns_rec") or is_admin()) print '<p><form method=post action=add_dns.php>
Add record: <select name=type>
<option value=A>A</option>
<option value=NS>NS</option>
<option value=MX>MX</option>
<option value=CNAME>CNAME</option>
<option value=PTR>PTR</option>
</select> <input type=submit value=Add>
<input type=hidden name=did value="' . $did . '">
</form>';
    
  }
  
}

nav_bottom();

?>