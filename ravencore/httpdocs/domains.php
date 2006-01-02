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

$domain_name = get_domain_name($did);

if($action == "delete") {

  $uid = get_uid_by_did($did);

  delete_domain($did);

  //the admin user is redirected with the uid in the url
  if(is_admin()) goto("domains.php?uid=$uid");
  else goto("domains.php");

} else if($action == "hosting") {

  $sql = "update domains set hosting = '$_POST[hosting]' where id = '$did'";
  mysql_query($sql);

  if( mysql_affected_rows() ) socket_cmd("rehash_httpd $domain_name");

  goto("domains.php?did=$did");

} else if($action == "change") {

  // only an admin can do this
  if(!is_admin()) goto("users.php");

  $sql = "update domains set uid = '$_POST[uid]' where id = '$did'";
  mysql_query($sql);

  goto("domains.php?did=$did");

}

if(!$did) {

  nav_top();

  //print who the domains are for, if we're the admin and we're looking at a specific user's domains
  if($uid and is_admin()) {

    $sql = "select * from users where id = '$uid'";
    $result = mysql_query($sql);

    $row_u = mysql_fetch_array($result);

    print '' . __('Domains for') . ' ' . $row_u[name] . '<p>';

  }

  $sql = "select * from domains where 1";

  if(!is_admin() or $uid) $sql .= " and uid = '$uid'";
  if($_GET[search]) $sql .= " and name like '%$_GET[search]%'";

  $sql .= " order by name";
  $result = mysql_query($sql);
  
  $num_domains = mysql_num_rows($result);
  
  if($num_domains == 0 and !$_GET[search]) {
   
    print __('There are no domains setup') . '.';

    // give an "add a domain" link if the user has permission to add one more
    if(is_admin() or user_have_permission($uid,"domain")) print ' <a href="edit_domain.php">' . __('Add a Domain') . '</a>';


  } else {

    print '<form method=get name=search>' . __('Search') . ': <input type=text name=search value="' . $_GET[search] . '">
<input type=submit value=' . __('Go') . ' onclick="if(!document.search.search.value) { alert(\'' . __('Please enter a search value!') . '\'); return false; }">';
    
    if($_GET[search]) print ' <input type=button value="' . __('Show All') . '" onclick="self.location=\'domains.php\'">';
    
    print '</form><p>';
  
  }
  
  if($_GET[search]) print '' . __('Your search returned') . ' <i><b>' . $num_domains . '</b></i> ' . __('results') . '.<p>';
  
  if($num_domains != 0) {
    
    print '<table><tr><th>' . __('Name') . '</th><th>' . __('Space usage') . '</th><th>' . __('Traffic usage') . '</th></tr>';

    //set our totals to zero
    $total_space = 0;
    $total_traffic = 0;
    
    while ( $row = mysql_fetch_array($result) ) {
      
      $space = domain_space_usage($row[id], date("m"), date("Y"));
      $traffic = domain_traffic_usage($row[id], date("m"), date("Y"));
      
      //add to our totals
      $total_space += $space;
      $total_traffic += $traffic;
      
      print '<tr><td><a href="domains.php?did=' . $row[id] . '" onmouseover="show_help(\'' . __('View setup information for') . ' ' . $row[name] . '\');" onmouseout="help_rst();">' . $row[name] . '</a></td><td align=right>' . $space . ' MB</td><td align=right>' . $traffic . ' MB</td></tr>';
      
    }
    
    print '<tr><td>' . __('Totals') . '</td><td align=right>' . $total_space . ' MB</td><td align=right>' . $total_traffic . ' MB</td></tr></table><p>';
    
    //print the link to add a domain if the user has permissions to
    if(!user_can_add($uid, "domain") and !is_admin()) print '' . __('You are at your limit for the number of domains you can have') . '<p>';
    else print '<a href="edit_domain.php" onmouseover="show_help(\'' . __('Add a domain to the server') . '\');" onmouseout="help_rst();">' . __('Add a Domain') . '</a><p>';
    
  }
  
} else {

  nav_top();

  $sql = "select * from domains where id = '$did'";
  if(!is_admin()) $sql .= " and uid = '$uid'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0) print __('Domain does not exist');
  else {

    $row = mysql_fetch_array($result);

    if(is_admin()) {

      $uid = $row[uid];
      
      print '<form method="post">' . __('This domain belongs to') . ': <select name=uid>';

      $sql = "select * from users";
      $result = mysql_query($sql);
      
      $num = mysql_num_rows($result);
      
      print '<option value=0>' . __('No One') . '</option>';

      while( $row_u = mysql_fetch_array($result) ) {
	
	print '<option value="' . $row_u[id] . '"';
	
	if($row_u[id] == $uid) print ' selected';
	
	print '>' . $row_u[name] . '</option>';
	
      }

      print '</select> <input type=submit value="' . __('Change') . '">
<input type=hidden name=action value=change>
<input type=hidden name=did value="' . $did . '">
</form>';

    }

    print '<table width="45%" style="float: left">
<tr><th colspan="2">' . __('Info for') . ' ' . $row[name] . '</th></tr>
<tr><td>' . __('Name') . ':</td><td>' . $row[name] . ' - <a href="domains.php?action=delete&did=' . $row[id] . '" onmouseover="show_help(\'' . __('Delete this domain off the server') . '\');" onmouseout="help_rst();" onclick="return confirm(\'' . __('Are you sure you wish to delete this domain') . '?\');">' . __('delete') . '</a></td></tr>';
    
    print '<tr><td>' . __('Created') . ':</td><td>' . $row[created] . '</td></tr>';

    if(have_service("web")) {
      
      print '<tr><td><form method="post" name=status>' . __('Status') . ':</td><td>';
      
      if($row[hosting] == "on") print '' . __('ON') . ' <a href="javascript:document.status.submit();" onclick="return confirm(\'' . __('Are you sure you wish to turn off hosting for this domain') . '?\');" onmouseover="show_help(\'' . __('Turn OFF hosting for this domain') . '\');" onmouseout="help_rst();">*</a><input type=hidden name=hosting value="off">';
      else print '' . __('OFF') . ' <a href="javascript:document.status.submit();" onmouseover="show_help(\'' . __('Turn ON hosting for this domain') . '\');" onmouseout="help_rst();">*</a><input type=hidden name=hosting value="on">';
      
      print '<input type=hidden name=did value=' . $did . '>
<input type=hidden name=action value=hosting>
</form></td></tr>
<tr><td>';
      
      switch($row[host_type]) {
	
      case "physical":
	print '' . __('Physical Hosting') . ':</td><td><a href="hosting.php?did=' . $row[id] . '" onmouseover="show_help(\'' . __('View/Edit Physical hosting for this domain') . '\');" onmouseout="help_rst();">' . __('edit') . '</a>';
	break;
      case "redirect":
	print '' . __('Redirect') . ':</td><td><a href="hosting.php?did=' . $row[id] . '" onmouseover="show_help(\'' . __('View/Edit where this domain redirects to') . '\');" onmouseout="help_rst();">' . __('edit') . '</a>';
	break;
      case "alias":
	print '' . __('Alias of') . ' ' . $row[redirect_url] . '</td><td> <a href="hosting.php?did=' . $row[id] . '" onmouseover="show_help(\'' . __('View/Edit what this domain is a server alias of') . '\');" onmouseout="help_rst();">' . __('edit') . '</a>';
	break;
      default:
	print '' . __('No Hosting') . ':</td><td><a href="hosting.php?did=' . $row[id] . '" onmouseover="show_help(\'' . __('Setup hosting for this domain') . '\');" onmouseout="help_rst();">' . __('edit') . '</a>';
	break;
	
      }
      
      print '</td></tr></table>

<table width="45%" style="float: right">
<tr><th colspan=2>Options</th></tr>
<tr><td>
';
      
      if($row[host_type] == "physical") {
	
	// the file manager make a connection to port 21 and uses FTP to manage files. If the ftp server is
	// offline, then we want to say that here.
	$ftp_working = @fsockopen("localhost", 21);

	if($ftp_working) print '<a href="filemanager.php?did=' . $did . '" target="_blank" onmouseover="show_help(\'' . __('Go to the File Manager for this domain') . '\');" onmouseout="help_rst();">';
	else print '<a href="#" onclick="alert(\'' . __('The file manager is currently offline') . '\')" onmouseover="show_help(\'' . __('The file manager is currently offline') . '\');" onmouseout="help_rst();">';

	print __('File Manager');

	if(!$ftp_working) print ' ( ' .__('offline') . ' )';

	print '</a>';

	// log manager currently disabled, it broke somewhere along the line :)
	//print '<p><a href="log_manager.php?did=' . $did . '" onmouseover="show_help(\'' . __('Go to the Log Manager for this domain') . '\');" onmouseout="help_rst();">' . __('Log Manager') . '</a><p>';
	
      }
      
      if($row[host_type] == "physical") print '<p><a href="error_docs.php?did=' . $did . '" onmouseover="show_help(\'' . __('View/Edit Custom Error Documents for this domain') . '\');" onmouseout="help_rst();">' . __('Error Documents') . '</a></p>';
      else {
	
	$sql = "delete from error_docs where did = '$did'";
	mysql_query($sql);
	
      }

    }

    if(have_service("mail")) {

      print '<p><a href="mail.php?did=' . $row[id] . '" onmouseover="show_help(\'' . __('View/Edit Mail for this domain') . '\');" onmouseout="help_rst();">' . __('Mail') . '</a>';

    if($row[mail] == "on") {
      
      $sql = "select count(*) as count from mail_users where did = '$row[id]'";
      $result = mysql_query($sql);
      
      $row_count = mysql_fetch_array($result);
      
      print ' (' . $row_count[count] . ')';

    } else print __('( off )');

    print '</p>';

    }

    print '<a href="databases.php?did=' . $row[id] . '" onmouseover="show_help(\'' . __('View/Edit databases for this domain') . '\');" onmouseout="help_rst();">' . __('Databases') . '</a>';

    $sql = "select count(*) as count from data_bases where did = '$row[id]'";
    $result = mysql_query($sql);

    $row_count = mysql_fetch_array($result);

    print ' (' . $row_count[count] . ')<p>';

    if(have_service("dns")) {

      print '<a href="dns.php?did=' . $did . '" onmouseover="show_help(\'' . __('Manage DNS for this domain') . '\');" onmouseout="help_rst();">' . __('DNS Records') . '</a>';

      if($row[soa]) {

	$sql = "select count(*) as count from dns_rec where did = '$row[id]'";
	$result = mysql_query($sql);
	
	$row_count = mysql_fetch_array($result);
	
	print ' (' . $row_count[count] . ')';
	
      } else print __('( off )');

      print '<p>';

    }

    if(have_service("web")) print '<a href="webstats.php?did=' . $row[id] . '" target=_blank onmouseover="show_help(\'' . __('View Webstats for this domain') . '\');" onmouseout="help_rst();">' . __('Webstats') . '</a>';

    print '</td></tr></table>';

      if($row[host_type] == "physical") {
	
	print '<table width="45%" style="float: left;margin-top: 10px">
<tr><th colspan="2">' . __('Domain Usage') . '</th></tr>
<tr><td>' . __('Disk space usage') . ': </td><td>' . domain_space_usage($did, date("m"), date("Y")) . 'MB </td></tr>
<tr><td>' . __('This month\'s bandwidth') . ': </td><td>' . domain_traffic_usage($did, date("m"), date("Y")) . 'MB</td></tr></table>';
	
      }

  }
    
}

nav_bottom();

?>