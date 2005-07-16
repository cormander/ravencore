<?php

include "auth.php";

if($action == "update") {

  if($_POST[catchall] == "true" and !preg_match('/^'.REGEX_MAIL_NAME.'@'.REGEX_DOMAIN_NAME.'$/',$_POST[catchall_addr])) alert("Invalid email address for catchall");
  else {
    
    $sql = "update domains set catchall = '$_POST[catchall]', catchall_addr = '$_POST[catchall_addr]', bounce_message = '$_POST[bounce_message]', alis_addr = '$_POST[alis_addr]' where id = '$did'";
    
    mysql_query($sql);
    
    if( mysql_affected_rows() ) socket_cmd("rehash_mail --all");
    
    goto("mail.php?did=$did");
    
  }

} else if($action == "delete") {

  delete_email($mid);
    
  goto("mail.php?did=$did");

} else if($action == "toggle") {

  $sql = "update domains set mail = '$_POST[mail]' where id = '$did'";
  mysql_query($sql);

  if( mysql_affected_rows() ) socket_cmd("rehash_mail --all");

  goto("mail.php?did=$did");

}

if($did) {

  //we include the nav_top inside the if statement, because if we're a user and try to view the page
  //we'll go to the else statement and the req_admin will print out a nav_top
  nav_top();

  $sql = "select * from domains where id = '$did'";
  $result = mysql_query($sql);
  
  $num = mysql_num_rows($result);
  
  if($num == 0) print "Domain does not exist";
  else {
    $row = mysql_fetch_array($result);

    print '<form method=post name=main>Mail for <a href="domains.php?did=' . $row[id] . '" onmouseover="show_help(\'Goto ' . $row[name] . '\');" onmouseout="help_rst();">' . $row[name] . '</a> is ';

    if($row[mail] != "on") print 'OFF <a href="javascript:document.main.submit();" onmouseover="show_help(\'Turn ON mail for ' . $row[name] . '\');" onmouseout="help_rst();">*</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=action value="toggle">
<input type=hidden name=mail value="on">
';
    else {
      print 'ON <a href="javascript:document.main.submit();" onmouseover="show_help(\'Turn OFF  mail for ' . $row[name] . '\');" onmouseout="help_rst();" onclick="return confirm(\'Are you sure you wish to disable mail for this domain?\');">*</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=action value="toggle">
<input type=hidden name=mail value="off">
</form>
<p>';
    /*
print '<form method=POST>
Mail to email accounts not set up for this domain:
<br>
<input type=radio name=catchall value=true';
      if($row[catchall] == "true") print ' checked';
      print '> Send to: <input type=text name=catchall_addr value="' . $row[catchall_addr] . '"> <br>
<input type=radio name=catchall value=false';
      if($row[catchall] == "false") print ' checked';
      print '> Bounce with: <input type=text name=bounce_message value="' . $row[bounce_message] . '"> <br>';

      $sql = "select count(*) as count from domains where uid = '$uid'";
      $result_count = mysql_query($sql);

      $row_c = mysql_fetch_array($result_count);

      //for domains with no user
      if($row_c[count] == 0) {

	print '<input type=radio name=catchall value=alis';
	if($row[catchall] == "alis") print ' checked';
	print '> Forwoard to that user @ <input type=text name=alis_addr value="' . $row[alis_addr] . '">';
	
      //for users with more then one domain setup
      } else if($row_c[count] > 1) {

	print '<input type=radio name=catchall value=alis';
        if($row[catchall] == "alis") print ' checked';
	print '> Forwoard to that user @ <select name=alis_addr>';
	
	//all other domains for this user ( with mail turned on )
	$sql = "select name from domains where uid = '$uid' and id != '$did' and mail = 'on'";
	$result_alis = mysql_query($sql);

	while( $row_a = mysql_fetch_array($result_alis) ) {

	  print '<option value="' . $row_a[name] . '"';
	  if($row[alis_addr] == $row_a[name]) print ' selected';
	  print '>' . $row_a[name] . '</option>';

	}

	print '</select>';

      } else print '<input type=radio disabled> You need at least two domains in the account with mail turned on to be able to alias mail';

      print '<p>
<input type=submit value="Update"> <input type=hidden name=did value="' . $row[id] . '"> <input type=hidden name=action value=update>
</form>
<p>';
    */
      
      $sql = "select * from mail_users where did = '$row[id]' order by mail_name";
      $result = mysql_query($sql);
      
      $num = mysql_num_rows($result);
      
      if($num == 0) print 'No mail for this domain.<p>';
      else print '<table><tr><th colspan=3>Mail for this domain:</th></tr>';
      
      print "";
      
      while( $row_email = mysql_fetch_array($result) ) {
	
	print '<tr><td><a href="edit_mail.php?did=' . $row_email[did] . '&mid=' . $row_email[id] . '" onmouseover="show_help(\'Edit ' . $row_email[mail_name] . '@' . $row[name] . '\');" onmouseout="help_rst();">edit</a></td><td>' . $row_email[mail_name] . '@' . $row[name] . '</td><td><a href=mail.php?did=' . $row[id] . '&mid=' . $row_email[id] . '&action=delete onmouseover="show_help(\'Delete ' . $row_email[mail_name] . '@' . $row[name] . '\');" onmouseout="help_rst();" onclick="';
	
	if(!user_can_add($uid,"email") and !is_admin()) print 'return confirm(\'If you delete this email, you may not be able to add it again.\rAre you sure you wish to do this?\');';
	else print 'return confirm(\'Are you sure you wish to delete this email?\');';
	print '">delete</a></td></tr>';
	
      }
      
      if($num != 0) print '</table>';

      if(user_can_add($uid,"email") or is_admin()) {
	
	print ' <a href="edit_mail.php?did=' . $row[id] . '"';
	
	if(!user_can_add($uid,"email") and is_admin()) print ' onclick="return confirm(\'This user is only allowed to create ' . user_have_permission($uid,"email") . ' email accounts. Are you sure you want to add another?\');"';
	
	print ' onmouseover="show_help(\'Add an email account\');" onmouseout="help_rst();">Add Mail</a>';
	
      }

    }
    
  }

} else {

  //req_admin();

  nav_top();

  // check to see if we have any domains setup at all. If not, die with this error
  
  $sql = "select count(*) as count from domains";
  if($uid) $sql .= " where uid = '$uid'";

  $result = mysql_query($sql);
  
  $row = mysql_fetch_array($result);
  
  if($row[count] == 0) {
    
    print 'You have no domains setup.';

    // give an "add a domain" link if the user has permission to add one more
    if(is_admin() or user_have_permission($uid,"domain")) print ' <a href="edit_domain.php">Add a Domain</a>';

    nav_bottom();
    
    exit;
    
  }
  
  print '<a href="edit_mail.php" onmouseover="show_help(\'Create a new email account\');" onmouseout="help_rst();">Add an email address</a>
<p>
<form method="GET" name=search>
   Search: <input type=text name=search value="' . $_GET[search] . '">
<input type=submit value="Go" onclick="if(!document.search.search.value) { alert(\'Please enter in a search value!\'); return false; }">';

  if($_GET[search]) print ' <input type=button value="Show All" onclick="self.location=\'mail.php\'">';

  print '</form><p>';

  if(is_admin()) {

    //admins get to see all domains
    $sql = "select m.id as mid, d.id as did, m.mail_name, d.name from mail_users m, domains d where did = d.id";
    if($_GET[search]) $sql .= " and ( m.mail_name like '%$_GET[search]%' or d.name like '%$_GET[search]%' or concat(m.mail_name, '@', d.name) like '%$_GET[search]%' )";
    $sql .= " order by mail_name";

  } else {

    //users only get to look at their own, so we look in the users table as well
    $sql = "select m.id as mid, d.id as did, m.mail_name, d.name from mail_users m, domains d, users u where did = d.id and d.uid = u.id and m.did = d.id and u.id = '$uid'";
    if($_GET[search]) $sql .= " and ( m.mail_name like '%$_GET[search]%' or d.name like '%$_GET[search]%' or concat(m.mail_name, '@', d.name) like '%$_GET[search]%' )";
    $sql .= " order by mail_name";

  }

  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0 and !$_GET[search]) print "There are no mail users setup";
  else if($_GET[search]) print 'Your search returned <i><b>' . $num . '</b></i> results<p>';

  if($num != 0) print '<table width="45%"><tr><th>Email Addresses</th></tr>';

  while( $row = mysql_fetch_array($result) ) {

    print '<tr><td><a href="edit_mail.php?did=' . $row[did] . '&mid=' . $row[mid] . '" onmouseover="show_help(\'Edit ' . $row[mail_name] . '@' . $row[name] . '\');" onmouseout="help_rst();">' . $row[mail_name] . '@' . $row[name] . '</td></tr>';

  }

  if($num != 0) print '</table>';

}

  
nav_bottom();
  
?>
