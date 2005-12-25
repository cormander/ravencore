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

    print '' . $lang['domains_domains_for'] . ' ' . $row_u[name] . '<p>';

  }

  $sql = "select * from domains where 1";

  if(!is_admin() or $uid) $sql .= " and uid = '$uid'";
  if($_GET[search]) $sql .= " and name like '%$_GET[search]%'";

  $sql .= " order by name";
  $result = mysql_query($sql);
  
  $num_domains = mysql_num_rows($result);
  
  if($num_domains == 0 and !$_GET[search]) {
   
    print $lang['domains_there_are_no_domains_setup'] . '.';

    // give an "add a domain" link if the user has permission to add one more
    if(is_admin() or user_have_permission($uid,"domain")) print ' <a href="edit_domain.php">Add a Domain</a>';


  } else {

    print '<form method=get name=search>' . $lang['global_search'] . ': <input type=text name=search value="' . $_GET[search] . '">
<input type=submit value=' . $lang['global_go'] . ' onclick="if(!document.search.search.value) { alert(\'' . $lang['global_please_enter_search_value'] . '\'); return false; }">';
    
    if($_GET[search]) print ' <input type=button value="' . $lang['global_show_all'] . '" onclick="self.location=\'domains.php\'">';
    
    print '</form><p>';
  
  }
  
  if($_GET[search]) print '' . $lang['global_your_search_returned'] . ' <i><b>' . $num_domains . '</b></i> ' . $lang['global_results'] . '<p>';
  
  if($num_domains != 0) {
    
    print '<table><tr><th>' . $lang['global_name'] . '</th><th>' . $lang['global_disc_space_usage'] . '</th><th>' . $lang['global_traffic_usage'] . '</th></tr>';

    //set our totals to zero
    $total_space = 0;
    $total_traffic = 0;
    
    while ( $row = mysql_fetch_array($result) ) {
      
      $space = domain_space_usage($row[id], date("m"), date("Y"));
      $traffic = domain_traffic_usage($row[id], date("m"), date("Y"));
      
      //add to our totals
      $total_space += $space;
      $total_traffic += $traffic;
      
      print '<tr><td><a href="domains.php?did=' . $row[id] . '" onmouseover="show_help(\'' . $lang['domains_view_setup_information_for'] . ' ' . $row[name] . '\');" onmouseout="help_rst();">' . $row[name] . '</a></td><td align=right>' . $space . ' MB</td><td align=right>' . $traffic . ' MB</td></tr>';
      
    }
    
    print '<tr><td>Totals</td><td align=right>' . $total_space . ' MB</td><td align=right>' . $total_traffic . ' MB</td></tr></table><p>';
    
    //print the link to add a domain if the user has permissions to
    if(!user_can_add($uid, "domain") and !is_admin()) print '' . $lang['domains_you_are_at_domain_limit'] . '<p>';
    else print '<a href="edit_domain.php" onmouseover="show_help(\'' . $lang['domains_add_a_domain_to_server'] . '\');" onmouseout="help_rst();">' . $lang['domains_add_a_domain'] . '</a><p>';
    
  }
  
} else {

  nav_top();

  $sql = "select * from domains where id = '$did'";
  if(!is_admin()) $sql .= " and uid = '$uid'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0) print $lang['domains_domain_no_exist'];
  else {

    $row = mysql_fetch_array($result);

    if(is_admin()) {

      $uid = $row[uid];
      
      print '<form method="post">' . $lang['domains_domain_belongs_to'] . ': <select name=uid>';

      $sql = "select * from users";
      $result = mysql_query($sql);
      
      $num = mysql_num_rows($result);
      
      print '<option value=0>' . $lang['domains_no_one'] . '</option>';

      while( $row_u = mysql_fetch_array($result) ) {
	
	print '<option value="' . $row_u[id] . '"';
	
	if($row_u[id] == $uid) print ' selected';
	
	print '>' . $row_u[name] . '</option>';
	
      }

      print '</select> <input type=submit value="' . $lang['domains_change'] . '">
<input type=hidden name=action value=change>
<input type=hidden name=did value="' . $did . '">
</form>';

    }

    print '<table width="45%" style="float: left">
<tr><th colspan="2">Info for ' . $row[name] . '</th></tr>
<tr><td>' . $lang['domains_name'] . ':</td><td>' . $row[name] . ' - <a href="domains.php?action=delete&did=' . $row[id] . '" onmouseover="show_help(\'' . $lang['domains_deletes_this_domain'] . '\');" onmouseout="help_rst();" onclick="return confirm(\'' . $lang['domains_sure_you_want_to_delete'] . '?\');">' . $lang['domains_delete'] . '</a></td></tr>';
    
    print '<tr><td>' . $lang['domains_created'] . ':</td><td>' . $row[created] . '</td></tr>';

    if(have_service("web")) {
      
      print '<tr><td><form method="post" name=status>' . $lang['domains_status'] . ':</td><td>';
      
      if($row[hosting] == "on") print '' . $lang['domains_on'] . ' <a href="javascript:document.status.submit();" onclick="return confirm(\'' . $lang['domains_sure_turn_off_hosting'] . '?\');" onmouseover="show_help(\'' . $lang['domains_turn_off_hosting'] . '\');" onmouseout="help_rst();">*</a><input type=hidden name=hosting value="off">';
      else print '' . $lang['domains_off'] . ' <a href="javascript:document.status.submit();" onmouseover="show_help(\'' . $lang['domains_turn_on_hosting'] . '\');" onmouseout="help_rst();">*</a><input type=hidden name=hosting value="on">';
      
      print '<input type=hidden name=did value=' . $did . '>
<input type=hidden name=action value=hosting>
</form></td></tr>
<tr><td>';
      
      switch($row[host_type]) {
	
      case "physical":
	print '' . $lang['domains_physical'] . ':</td><td><a href="hosting.php?did=' . $row[id] . '" onmouseover="show_help(\'' . $lang['domains_view_edit_physical'] . '\');" onmouseout="help_rst();">' . $lang['domains_edit'] . '</a>';
	break;
      case "redirect":
	print '' . $lang['domains_redirect'] . ':</td><td><a href="hosting.php?did=' . $row[id] . '" onmouseover="show_help(\'' . $lang['domains_view_edit_redirect'] . '\');" onmouseout="help_rst();">' . $lang['domains_edit'] . '</a>';
	break;
      case "alias":
	print '' . $lang['domains_alias'] . ' ' . $row[redirect_url] . '</td><td> <a href="hosting.php?did=' . $row[id] . '" onmouseover="show_help(\'' . $lang['domains_view_edit_alias'] . '\');" onmouseout="help_rst();">' . $lang['domains_edit'] . '</a>';
	break;
      default:
	print '' . $lang['domains_no_hosting'] . ':</td><td><a href="hosting.php?did=' . $row[id] . '" onmouseover="show_help(\'' . $lang['domains_setup_hosting'] . '\');" onmouseout="help_rst();">' . $lang['domains_setup'] . '</a>';
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

	if($ftp_working) print '<a href="filemanager.php?did=' . $did . '" target="_blank" onmouseover="show_help(\'' . $lang['domains_go_to_filemanager'] . '\');" onmouseout="help_rst();">';
	else print '<a href="#" onclick="alert(\'' . $lang['domians_offline_filemanager'] . '\')" onmouseover="show_help(\'' . $lang['domains_filemanager_currently_offline'] . '\');" onmouseout="help_rst();">';

	print $lang['domains_filemanager'];

	if(!$ftp_working) print $lang['domains_filemanager_offline'];

	print '</a>';

	// log manager currently disabled, it broke somewhere along the line :)
	//print '<p><a href="log_manager.php?did=' . $did . '" onmouseover="show_help(\'' . $lang['domains_go_to_log_manager'] . '\');" onmouseout="help_rst();">' . $lang['domains_log_manager'] . '</a><p>';
	
      }
      
      if($row[host_type] == "physical") print '<p><a href="error_docs.php?did=' . $did . '" onmouseover="show_help(\'' . $lang['domains_view_edit_ced'] . '\');" onmouseout="help_rst();">' . $lang['domains_error_docs'] . '</a></p>';
      else {
	
	$sql = "delete from error_docs where did = '$did'";
	mysql_query($sql);
	
      }

    }

    if(have_service("mail")) {

      print '<p><a href="mail.php?did=' . $row[id] . '" onmouseover="show_help(\'' . $lang['domains_view_edit_mail'] . '\');" onmouseout="help_rst();">' . $lang['domains_mail'] . '</a>';

    if($row[mail] == "on") {
      
      $sql = "select count(*) as count from mail_users where did = '$row[id]'";
      $result = mysql_query($sql);
      
      $row_count = mysql_fetch_array($result);
      
      print ' (' . $row_count[count] . ')';

    } else print $lang['domains_mail_off'];

    print '</p>';

    }

    print '<a href="databases.php?did=' . $row[id] . '" onmouseover="show_help(\'' . $lang['domains_view_edit_domain_databases'] . '\');" onmouseout="help_rst();">' . $lang['domains_databases'] . '</a>';

    $sql = "select count(*) as count from data_bases where did = '$row[id]'";
    $result = mysql_query($sql);

    $row_count = mysql_fetch_array($result);

    print ' (' . $row_count[count] . ')<p>';

    if(have_service("dns")) {

      print '<a href="dns.php?did=' . $did . '" onmouseover="show_help(\'' . $lang['domains_manage_dns'] . '\');" onmouseout="help_rst();">' . $lang['domains_dns_records'] . '</a>';

      if($row[soa]) {

	$sql = "select count(*) as count from dns_rec where did = '$row[id]'";
	$result = mysql_query($sql);
	
	$row_count = mysql_fetch_array($result);
	
	print ' (' . $row_count[count] . ')';
	
      } else print $lang['domains_dns_off'];

      print '<p>';

    }

    if(have_service("web")) print '<a href="webstats.php?did=' . $row[id] . '" target=_blank onmouseover="show_help(\'' . $lang['domains_view_webstats'] . '\');" onmouseout="help_rst();">' . $lang['domains_webstats'] . '</a>';

    print '</td></tr></table>';

      if($row[host_type] == "physical") {
	
	print '<table width="45%" style="float: left;margin-top: 10px">
<tr><th colspan="2">Domain Usage</th></tr>
<tr><td>Disk space usage:</td><td>' . domain_space_usage($did, date("m"), date("Y")) . 'MB </td></tr>
<tr><td>This month\'s bandwidth:</td><td>' . domain_traffic_usage($did, date("m"), date("Y")) . 'MB</td></tr></table>';
	
      }

  }
    
}

nav_bottom();

?>