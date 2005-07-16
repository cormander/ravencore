<?php

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
' . $lang['search'] . ': <input type=text name=search value="' . $_GET[search] . '">
<input type=submit value="' . $lang['go'] . '" onclick="if(!document.search.search.value) { alert(\'' . $lang['please_enter_search_val2'] . '\'); return
false; }">';

  if($_GET[search]) print ' <input type=button value="Show All" onclick="self.location=\'' . $_SERVER[PHP_SELF] . '\'">';

  print '</form><p>';

  $sql = "select * from users";
  if($_GET[search]) $sql .= " where name like '%$_GET[search]%'";
  $sql .= " order by name";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0 and !$_GET[search]) print "There are no users setup";
  else if($_GET[search]) print 'Your search returned <i><b>' . $num . '</b></i> results<p>';
  //else print 'A total of <i><b></b></i>';

  if($num != 0) {

    print '<table><tr><th>Name</th><th>Domains</th><th>Space Usage</th><th>Traffic Usage</th>
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

      print '<tr><td><a href="users.php?uid=' . $row[id] . '" onmouseover="show_help(\'View user data for ' . $row[name] . '\');" onmouseout="help_rst();">' . $row[name] . '</a></td><td align=right>' . $domains . '</td><td align=right>' . $space . ' MB</td><td align=right>' . $traffic . ' MB</td></tr>';

    }

    print '<tr><td>Totals</td></td><td align=right>' . $total_domains . '</td><td align=right>' . $total_space . ' MB</td><td align=right>' . $total_traffic . ' MB</td></tr></table>';

  }

  print '<p><a href="edit_user.php" onmouseover="show_help(\'Add a user to the control panel\');" onmouseout="help_rst();">Add a Control Panel User</a>';

} else {

  $sql = "select * from users where id = '$uid'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0) print "User does not exist";
  else {
    $row = mysql_fetch_array($result);

    //look in the login_failure table to see if this user is locked out
  $sql = "select count(*) as count from login_failure where login = '$row[login]' and ( ( to_days(date) * 24 * 60 * 60 ) + time_to_sec(date) + $CONF[LOCKOUT_TIME] ) > ( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) )";

    $result = mysql_query($sql);

    $row_lock = mysql_fetch_array($result);

    //if they are locked out, provide a way to unlock it
    if(is_admin() and $row_lock[count] >= $CONF[LOCKOUT_COUNT]) print '<font color="red"><b>This user is locked out due to failed login attempts</b></font> - <a href="users.php?action=unlock&login=' . $row[login] . '&uid=' . $row[id] . '">Unlock</a>';

    print '
            <table width="45%" style="float: left">
                <tr>
         <th colspan=2>Info for <strong>' . $row[name] . '</strong></th>
                </tr>';
    /*
                <tr>
	 <td valign="top">Company:</td>
	 <td valign="top">' . $row[company] . '&nbsp;</td>
                </tr>
                <tr>
    */
    print '<td valign="top">Created:</td>
	 <td valign="top">' . $row[created] . '&nbsp;</td>
                </tr>
                <tr>
	 <td valign="top">Contact E-mail:</td>
	 <td valign="top">' . $row[email] . '&nbsp;</td>
	        </tr>
                <tr>
	 <td valign="top">Login ID:</td>
	 <td valign="top">' . $row[login] . '&nbsp;</td>
                </tr>
                <tr>
	 <td colspan=2 valign="top">&nbsp;</td>
                </tr>
                <tr>
	 <td valign="top"><a href="edit_user.php';
    
    //only the admin can see the uid
    if(is_admin()) print '?uid=' . $row[id] . '';
    
    print '" onmouseover="show_help(\'Edit account information\');" onmouseout="help_rst();">Edit Account Info</a></td>
         <td valign="top"><a href="user_permissions.php';
    
    //the admin sees the uid on this link
    if(is_admin()) print '?uid=' . $uid;
    
    print '" onmouseover="show_help(\'See what you can and can\\\'t do\');" onmouseout="help_rst();">';
    
    if(is_admin()) print 'View/Edit Permissions';
    else print 'View your Permissions';
    
    print '</a></td>
                </tr>
            </table>

	    <table width="45%" style="float: right">
  	        <tr><th>Options</th></tr>
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
      if(!is_admin()) print 'You have no domains setup';
      else print 'No domains setup';
      print '<p>';

    } else {

      print '<form name=main><div id="didsel" style="visibility: hidden;">
<select name="did" onchange="if(did.value!=0) document.main.submit();"><option value=0>For which domain?</option>';
      
      while( $row_domain = mysql_fetch_array($result) ) {
	
	print '<option value="' . $row_domain[id] . '">' . $row_domain[name] . '</option>';
	
      }
      
      if($num != 0) print '</select> <input type=submit value=Go><br><a href="#" onclick="men_toggle();">Back</a></div></form>';

      print '<div id="mainmenu">';

      if(have_service("mysql")) print '<p>
<a href="#" onmouseover="show_help(\'Add a MySQL database\');" onmouseout="help_rst();" onclick="sel_toggle(\'add_db.php\');">Add MySQL Database</a><p>';
      
      if(have_service("mail")) print '<a href="#" onmouseover="show_help(\'Add an email account\');" onmouseout="help_rst();" onclick="sel_toggle(\'edit_mail.php?page_type=add\');">Add E-Mail Account</a><p>';
      
      if(have_service("dns")) print '<a href="#" onmouseover="show_help(\'Add/Edit DNS records\');" onmouseout="help_rst();" onclick="sel_toggle(\'dns.php\');">Add/Edit DNS</a><p>';

      //the admin user can see the uid here
      print '<a href="domains.php';
      if(is_admin()) print '?uid=' . $uid;
      print '" onmouseover="show_help(\'List all of your domain names\');" onmouseout="help_rst();">List Domains</a><p>';
    
    }
    
    if(have_domain_services()) {
      
      //print the link to add a domain if the user has permissions to
      if(user_can_add($uid, "domain")) {

	print '<a href="edit_domain.php';
	if(is_admin()) print '?uid=' . $uid;
	print '" onmouseover="show_help(\'Add a domain to the server\');" onmouseout="help_rst();">Add a Domain</a>';
	
      } else {
	
	//users see a different message here than the admin
	if(!is_admin()) print 'You are at your limit for the number of domains you can have';
	else print 'This user is at his/her domain limit - <a href="edit_domain.php?uid=' . $uid . '">Add one anyway</a>';
	
      }

    }
    
    //close the div tag if we have more than 0 num_domains
    if($num_domains > 0) print '</div>';
    
    print '
    </td>
            </tr>
            </table>

<table width="45%" style="float: left; margin-top: 10px">
<tr><th colspan=2>Domain Usage</th></tr>
<tr><td>Disk Spage usage:</td><td align=right>' . user_space_usage($uid, date("m"), date("Y") ) . ' MB</td></tr>
<tr><td>Traffic usage (current month):</td><td align=right>' . user_traffic_usage($uid, date("m"), date("Y") ) . ' MB</td></tr></table>';
  
  }
  
}

nav_bottom();

?>