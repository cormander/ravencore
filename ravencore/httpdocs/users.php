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
' . __('Search') . ': <input class="textfield" type=text name=search value="' . $_GET[search] . '">
<input type=submit value="' . __('Go') . '" onclick="if(!document.search.search.value) { alert(\'' . __('Please enter a search value!') . '\'); return
false; }">';

  if($_GET[search]) print ' <input type=button value="' . __('Show All') . '" onclick="self.location=\'' . $_SERVER[PHP_SELF] . '\'">';

  print '</form><p>';

  $sql = "select * from users";
  if($_GET[search]) $sql .= " where name like '%$_GET[search]%'";
  $sql .= " order by name";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0 and !$_GET[search]) print __('There are no users setup');
  else if($_GET[search]) print __('Your search returned') . ' <i><b>' . $num . '</b></i> ' . __('results') . '.<p>';
  //else print 'A total of <i><b></b></i>';

  if($num != 0) {

    print '<table><tr><th>' . __('Name') . '</th><th>' . __('Domains') . '</th><th>' . __('Space usage') . '</th><th>' . __('Traffic usage') . '</th>
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

      print '<tr><td><a href="users.php?uid=' . $row[id] . '" onmouseover="show_help(\' ' . __('View user data for') . ' ' . $row[name] . '\');" onmouseout="help_rst();">' . $row[name] . '</a></td><td align=right>' . $domains . '</td><td align=right>' . $space . ' MB</td><td align=right>' . $traffic . ' MB</td></tr>';

    }

    print '<tr><td>' . __('Totals') . '</td></td><td align=right>' . $total_domains . '</td><td align=right>' . $total_space . ' MB</td><td align=right>' . $total_traffic . ' MB</td></tr></table>';

  }

  print '<p><a href="edit_user.php" onmouseover="show_help(\' ' . __('Add a user to the control panel') . '\');" onmouseout="help_rst();">' . __('Add a Control Panel user') . '</a>';

} else {

  $sql = "select * from users where id = '$uid'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0) print __('User does not exist');
  else {
    $row = mysql_fetch_array($result);

    //look in the login_failure table to see if this user is locked out
  $sql = "select count(*) as count from login_failure where login = '$row[login]' and ( ( to_days(date) * 24 * 60 * 60 ) + time_to_sec(date) + $CONF[LOCKOUT_TIME] ) > ( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) )";

    $result = mysql_query($sql);

    $row_lock = mysql_fetch_array($result);

    //if they are locked out, provide a way to unlock it
    if(is_admin() and $row_lock[count] >= $CONF[LOCKOUT_COUNT]) print '<font color="red"><b>' . __('This user is locked out due to failed login attempts') . '</b></font> - <a href="users.php?action=unlock&login=' . $row[login] . '&uid=' . $row[id] . '">' . __('Unlock') . '</a>';

    print '
            <table width="45%" style="float: left">
                <tr>
         <th colspan=2>' . __('Info for') . ' <strong>' . $row[name] . '</strong></th>
                </tr>
                <tr>';

    print '<td valign="top">' . __('Company') . ':</td>
	 <td valign="top">' . $row[company] . '&nbsp;</td>
                </tr>
                <tr>';
    
    print '<td valign="top">' . __('Created') . ':</td>
	 <td valign="top">' . $row[created] . '&nbsp;</td>
                </tr>
                <tr>
	 <td valign="top">' . __('Contact email') . ':</td>
	 <td valign="top">' . $row[email] . '&nbsp;</td>
	        </tr>
                <tr>
	 <td valign="top">' . __('Login ID') . ':</td>
	 <td valign="top">' . $row[login] . '&nbsp;</td>
                </tr>
                <tr>
	 <td colspan=2 valign="top">&nbsp;</td>
                </tr>
                <tr>
	 <td valign="top"><a href="edit_user.php';
    
    //only the admin can see the uid
    if(is_admin()) print '?uid=' . $row[id] . '';
    
    print '" onmouseover="show_help(\'' . __('Edit account info') . '  \');" onmouseout="help_rst();">' . __('Edit account info') . '</a></td>
         <td valign="top"><a href="user_permissions.php';
    
    //the admin sees the uid on this link
    if(is_admin()) print '?uid=' . $uid;
    
    print '" onmouseover="show_help(\'' . __('See what you can and can not do') . ' \');" onmouseout="help_rst();">';
    
    if(is_admin()) print __('View/Edit Permissions');
    else print __('View Permissions');
    
    print '</a></td>
                </tr>
            </table>

	    <table width="45%" style="float: right">
  	        <tr><th>' . __('Options') . '</th></tr>
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
      if(!is_admin()) print __('You have no domains setup');
      else print __('No domains setup');
      print '<p>';

    } else {

      print '<form name=main><div id="didsel" style="visibility: hidden;">
<select name="did" onchange="if(did.value!=0) document.main.submit();"><option value=0>' . __('For which domain') . '?</option>';
      
      while( $row_domain = mysql_fetch_array($result) ) {
	
	print '<option value="' . $row_domain[id] . '">' . $row_domain[name] . '</option>';
	
      }
      
      if($num != 0) print '</select> <input type=submit value=Go><br><a href="#" onclick="men_toggle();">' . __('Back') . '</a></div></form>';

      print '<div id="mainmenu">';

      if(have_service("mysql")) print '<p>
<a href="#" onmouseover="show_help(\'' . __('Add a MySQL database') . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'add_db.php\');">' . __('Add a MySQL database') . '</a><p>';
      
      if(have_service("mail")) print '<a href="#" onmouseover="show_help(\'' . __('Add E-Mail Account') . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'edit_mail.php?page_type=add\');">' . __('Add E-Mail Account') . '</a><p>';
      
      if(have_service("dns")) print '<a href="#" onmouseover="show_help(\'' . __('Add/Edit DNS records') . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'dns.php\');">' . __('Add/Edit DNS records') . '</a><p>';

      if(have_service("web")) print '<a href="#" onmouseover="show_help(\'' . __('View Webstatistics') . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'webstats.php\');">' . __('View Webstatistics') . '</a><p>';

      //the admin user can see the uid here
      print '<a href="domains.php';
      if(is_admin()) print '?uid=' . $uid;
      print '" onmouseover="show_help(\'' . __('List all of your domain names') . '\');" onmouseout="help_rst();">' . __('List Domains') .'</a><p>';
    
    }
    
    if(have_domain_services()) {
      
      //print the link to add a domain if the user has permissions to
      if(user_can_add($uid, "domain")) {

	print '<a href="edit_domain.php';
	if(is_admin()) print '?uid=' . $uid;
	print '" onmouseover="show_help(\'' . __('Add a domain to the server') . '\');" onmouseout="help_rst();">' . __('Add a Domain') . '</a>';
	
      } else {
	
	//users see a different message here than the admin
	if(!is_admin()) print '' . __('You are at your limit for the number of domains you can have') . '';
	else print '' . __('This user is at his/her domain limit') . ' - <a href="edit_domain.php?uid=' . $uid . '">' . __('Add one anyway') . '</a>';
	
      }

    }
    
    //close the div tag if we have more than 0 num_domains
    if($num_domains > 0) print '</div>';
    
    print '
    </td>
            </tr>
            </table>

<table width="45%" style="float: left; margin-top: 10px">
<tr><th colspan=2>' . __('Domain usage') . '</th></tr>
<tr><td>' . __('Space usage') . ':</td><td align=right>' . user_space_usage($uid, date("m"), date("Y") ) . ' MB</td></tr>
<tr><td>' . __('Traffic usage (This month)') . ':</td><td align=right>' . user_traffic_usage($uid, date("m"), date("Y") ) . ' MB</td></tr></table>';
  
  }
  
}

nav_bottom();

?>