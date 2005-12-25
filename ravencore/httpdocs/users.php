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

include("auth.php");

if($action == "delete" and is_admin()) {

  delete_user($uid);

  goto("$_SERVER[PHP_SELF]");

} else if($action == "unlock" and is_admin()) {

  $sql = "delete from login_failure where login = '$_GET[login]'";
  mysql_query($sql);

  goto("users.php?uid=$uid");

}

nav_top();

//We don't have to worry about checking if we're an admin here, because
//the $uid variable will always be set if we're a user

if(!$uid) {

print '<form method="get" name=search>
' . $lang['global_search'] . ': <input class="textfield" type=text name=search value="' . $_GET[search] . '">
<input type=submit value="' . $lang['global_go'] . '" onclick="if(!document.search.search.value) { alert(\'' . $lang['global_please_enter_search_value'] . '\'); return
false; }">';

  if($_GET[search]) print ' <input type=button value="' . $lang['global_show_all'] . '" onclick="self.location=\'' . $_SERVER[PHP_SELF] . '\'">';

  print '</form><p>';

  $sql = "select * from users";
  if($_GET[search]) $sql .= " where name like '%$_GET[search]%'";
  $sql .= " order by name";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0 and !$_GET[search]) print $lang['users_no_users_setup'];
  else if($_GET[search]) print '' . $lang['global_your_search_returned'] . ' <i><b>' . $num . '</b></i> ' . $lang['global_results'] . '<p>';
  //else print 'A total of <i><b></b></i>';

  if($num != 0) {

    print '<table><tr><th>' . $lang['global_name'] . '</th><th>' . $lang['global_domains'] . '</th><th>' . $lang['global_disc_space_usage'] . '</th><th>' . $lang['global_traffic_usage'] . '</th>
</tr>';

    //set our totals to zero
    $total_space = 0;
    $total_traffic = 0;

    while ( $row = mysql_fetch_array($result) ) {

      $space = user_space_usage($row[id], date("m"), date("Y"));
      $traffic = user_traffic_usage($row[id], date("m"), date("Y"));
      $domains = num_domains($row[id]);

      //add to our totals
      $total_space += $space;
      $total_traffic += $traffic;
      $total_domains += $domains;

      print '<tr><td><a href="users.php?uid=' . $row[id] . '" onmouseover="show_help(\' ' . $lang['users_view_user_data_for'] . ' ' . $row[name] . '\');" onmouseout="help_rst();">' . $row[name] . '</a></td><td align=right>' . $domains . '</td><td align=right>' . $space . ' MB</td><td align=right>' . $traffic . ' MB</td></tr>';

    }

    print '<tr><td>' . $lang['global_totals'] . '</td></td><td align=right>' . $total_domains . '</td><td align=right>' . $total_space . ' MB</td><td align=right>' . $total_traffic . ' MB</td></tr></table>';

  }

  print '<p><a href="edit_user.php" onmouseover="show_help(\' ' . $lang['users_add_cp_user'] . '\');" onmouseout="help_rst();">' . $lang['users_add_a_cp_user'] . '</a>';

} else {

  $sql = "select * from users where id = '$uid'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0) print $lang['users_user_does_not_exist'];
  else {
    $row = mysql_fetch_array($result);

    //look in the login_failure table to see if this user is locked out
  $sql = "select count(*) as count from login_failure where login = '$row[login]' and ( ( to_days(date) * 24 * 60 * 60 ) + time_to_sec(date) + $CONF[LOCKOUT_TIME] ) > ( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) )";

    $result = mysql_query($sql);

    $row_lock = mysql_fetch_array($result);

    //if they are locked out, provide a way to unlock it
    if(is_admin() and $row_lock[count] >= $CONF[LOCKOUT_COUNT]) print '<font color="red"><b>' . $lang['users_failed_login_lockout'] . '</b></font> - <a href="users.php?action=unlock&login=' . $row[login] . '&uid=' . $row[id] . '">Unlock</a>';

    print '
            <table width="45%" style="float: left">
                <tr>
         <th colspan=2>Info for <strong>' . $row[name] . '</strong></th>
                </tr>
                <tr>';

    print '<td valign="top">' . $lang['users_company'] . ':</td>
	 <td valign="top">' . $row[company] . '&nbsp;</td>
                </tr>
                <tr>';
    
    print '<td valign="top">' . $lang['users_created'] . ':</td>
	 <td valign="top">' . $row[created] . '&nbsp;</td>
                </tr>
                <tr>
	 <td valign="top">' . $lang['users_contact_email'] . ':</td>
	 <td valign="top">' . $row[email] . '&nbsp;</td>
	        </tr>
                <tr>
	 <td valign="top">' . $lang['users_login_id'] . ':</td>
	 <td valign="top">' . $row[login] . '&nbsp;</td>
                </tr>
                <tr>
	 <td colspan=2 valign="top">&nbsp;</td>
                </tr>
                <tr>
	 <td valign="top"><a href="edit_user.php';
    
    //only the admin can see the uid
    if(is_admin()) print '?uid=' . $row[id] . '';
    
    print '" onmouseover="show_help(\'' . $lang['users_edit_account_info'] . '  \');" onmouseout="help_rst();">' . $lang['users_edit_account_info'] . '</a></td>
         <td valign="top"><a href="user_permissions.php';
    
    //the admin sees the uid on this link
    if(is_admin()) print '?uid=' . $uid;
    
    print '" onmouseover="show_help(\'' . $lang['users_see_what_you_can_and_not_do'] . ' \');" onmouseout="help_rst();">';
    
    if(is_admin()) print $lang['users_view_edit_perms'];
    else print $lang['users_view_perms'];
    
    print '</a></td>
                </tr>
            </table>

	    <table width="45%" style="float: right">
  	        <tr><th>' . $lang['users_options'] . '</th></tr>
                <tr>
	 <td valign="top" width="50%">

<script type="text/javascript">

function sel_toggle(a) {

document.main.action=a

didsel.style.visibility=\'visible\'
mainmenu.style.visibility=\'hidden\'

}

function men_toggle() {

didsel.style.visibility=\'hidden\'
mainmenu.style.visibility=\'visible\'

}

</script>
';
    
    $sql = "select * from domains where uid = '$uid'";
    $result = mysql_query($sql);

    $num_domains = mysql_num_rows($result);

    if($num_domains == 0) {

      //users will see a different message here than the admin
      if(!is_admin()) print $lang['users_you_have_no_domains_setup'];
      else print $lang['users_no_domains_setup'];
      print '<p>';

    } else {

      print '<form name=main><div id="didsel" style="visibility: hidden;">
<select name="did" onchange="if(did.value!=0) document.main.submit();"><option value=0>' . $lang['users_for_which_domain'] . '?</option>';
      
      while( $row_domain = mysql_fetch_array($result) ) {
	
	print '<option value="' . $row_domain[id] . '">' . $row_domain[name] . '</option>';
	
      }
      
      if($num != 0) print '</select> <input type=submit value=Go><br><a href="#" onclick="men_toggle();">' . $lang['global_back'] . '</a></div></form>';

      print '<div id="mainmenu">';

      if(have_service("mysql")) print '<p>
<a href="#" onmouseover="show_help(\'' . $lang['users_add_mysql_database'] . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'add_db.php\');">' . $lang['users_add_mysql_database'] . '</a><p>';
      
      if(have_service("mail")) print '<a href="#" onmouseover="show_help(\'' . $lang['users_add_email_account'] . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'edit_mail.php?page_type=add\');">' . $lang['users_add_email_account'] . '</a><p>';
      
      if(have_service("dns")) print '<a href="#" onmouseover="show_help(\'' . $lang['users_add_edit_dns'] . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'dns.php\');">' . $lang['users_add_edit_dns'] . '</a><p>';

      if(have_service("web")) print '<a href="#" onmouseover="show_help(\'' . $lang['users_view_webstats'] . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'webstats.php\');">' . $lang['users_view_webstats'] . '</a><p>';

      //the admin user can see the uid here
      print '<a href="domains.php';
      if(is_admin()) print '?uid=' . $uid;
      print '" onmouseover="show_help(\'' . $lang['users_list_all_your_domains'] . '\');" onmouseout="help_rst();">' . $lang['users_list_domains'] .'</a><p>';
    
    }
    
    if(have_domain_services()) {
      
      //print the link to add a domain if the user has permissions to
      if(user_can_add($uid, "domain")) {

	print '<a href="edit_domain.php';
	if(is_admin()) print '?uid=' . $uid;
	print '" onmouseover="show_help(\'' . $lang['users_add_a_domain_to_the_server'] . '\');" onmouseout="help_rst();">' . $lang['users_add_a_domain'] . '</a>';
	
      } else {
	
	//users see a different message here than the admin
	if(!is_admin()) print '' . $lang['users_domain_limit_reached'] . '';
	else print '' . $lang['users_user_reached_domain_limit'] . ' - <a href="edit_domain.php?uid=' . $uid . '">' . $lang['users_add_one_anyway'] . '</a>';
	
      }

    }
    
    //close the div tag if we have more than 0 num_domains
    if($num_domains > 0) print '</div>';
    
    print '
    </td>
            </tr>
            </table>

<table width="45%" style="float: left; margin-top: 10px">
<tr><th colspan=2>' . $lang[global_domain_usage] . '</th></tr>
<tr><td>' . $lang[global_disc_space_usage] . ':</td><td align=right>' . user_space_usage($uid, date("m"), date("Y") ) . ' MB</td></tr>
<tr><td>' . $lang[global_traffic_usage_current_month] . ':</td><td align=right>' . user_traffic_usage($uid, date("m"), date("Y") ) . ' MB</td></tr></table>';
  
  }
  
}

nav_bottom();

?>