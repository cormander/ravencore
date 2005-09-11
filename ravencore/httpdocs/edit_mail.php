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

$email_user_page = 1;

include "auth.php";

req_service("mail");

// if there iss't a mid, we assume add
if(!$mid) $page_type = "add";
// if there isn't a page type, we assume edit
if(!$page_type) $page_type = "edit";

// A mail user can't add another mail user
if($page_type == "add" and $row_email_user) goto("edit_mail.php");

if(!user_can_add($uid,"email") and !is_admin() and $page_type == "add") goto("users.php?uid=$uid");

if($action) {

  $sql = "select count(*) as count from mail_users where mail_name = '$_POST[name]' and did = '$did'";
  $result = mysql_query($sql);
  $row = mysql_fetch_array($result);

  if($row[count] != 0 and $page_type == "add") alert("That email address already exists");
  else {

    // Make sure that the passwords match

    if($_POST[confirm_passwd] != $_POST[passwd]) {

      alert("Your passwords do not match");
      $_POST[confirm_passwd] = "";
      $_POST[passwd] = "";

    } else {
      
      // Make sure the given mailname is valid
      
      if(preg_match('/^'.REGEX_MAIL_NAME.'$/',$_POST[name])) {
	
	if($_POST[redirect] and !$_POST[redirect_addr]) {
	  
	  alert("You selected you wanted a redirect, but left the address blank");
	  $select = "redirect_addr";
	  
	} else {
	  
	  $_POST[redirect_addr] = trim(ereg_replace(' ','',$_POST[redirect_addr]));

	  $redir_error = 0;

	  if($_POST[redirect_addr]) {

	    $addrs = explode(',',$_POST[redirect_addr]);

	    foreach( $addrs as $email ) {

	      if(!preg_match('/^'.REGEX_MAIL_NAME.'@'.REGEX_DOMAIN_NAME.'$/',$email)) $redir_error = 1;
	  
	    }


	  }

	  if($redir_error == 0) {
  
	    if(preg_match('/^'.REGEX_PASSWORD.'$/',$_POST[passwd])) {
	      
	      if($page_type == "add") $sql = "insert into mail_users set mail_name = '$_POST[name]', did = '$did', passwd = '$_POST[passwd]', spamassassin = '$_POST[spamassassin]', mailbox = '$_POST[mailbox]', redirect = '$_POST[redirect]', redirect_addr = '$_POST[redirect_addr]'";
	      else $sql = "update mail_users set passwd = '$_POST[passwd]', mailbox = '$_POST[mailbox]', spamassassin = '$_POST[spamassassin]', redirect = '$_POST[redirect]', redirect_addr = '$_POST[redirect_addr]' where id = '$mid'";
	      
	      mysql_query($sql);
	      
	      socket_cmd("rehash_mail --all");
	      
	      goto("mail.php?did=$did");
	      
	    } else {
	      
	      alert("Invalid password. Must only contain letters and numbers");
	      $_POST[passwd] = "";
	      $select = "passwd";
	      
	    }
	    
	  } else {
	    
	    alert("The redirect list contains an invalid email address.");
	    $_POST[redirect_addr] = "";
	    $select = "redirect_addr";
	    
	  }
	  
	}
	
      } else {
	
	// We failed to pass the mailname regex
	alert("Invalid mailname. It may only contain letters, number, dashes, dots, and underscores. Must both start and end with either a letter or number.");
	$_POST[name] = "";
	$select = "name";
	
      }
      
    }
    
  }

}
  
nav_top();

// check to make sure that mail is actually turned on for the $did, if we have one

if($did) {

  $sql = "select * from domains where id = '$did'";
  $result = mysql_query($sql);
  
  $row_chk = mysql_fetch_array($result);
  
  if($row_chk[mail] != "on") {
    
    print 'Mail is disabled for ' . $row_chk[name] . '. You can not add an email address for it.';
    
    nav_bottom();
    
    exit;
    
  }

}

// Define the javascript variable "tmp" to blank

print '<script type="text/javascript"> var tmp=""</script>';

if($page_type == "edit") {

  $sql = "select * from mail_users where id = '$mid'";
  $result = mysql_query($sql);

  $row_mail = mysql_fetch_array($result);

  $_POST[name] = $row_mail[mail_name];

}

?>

<form method="post" name="main">

<table>
<tr><th colspan="2"><?php

print ($mid ? 'Edit' : 'Add');

?> mail</th><th><?php

?></th></tr>

<tr><td>Mail Name:</td><td><?php

if($page_type == "add") {

?><input type="text" name="name"<?php if($_POST[name]) print ' value="' . $_POST[name] . '"'; ?>><?php

} else print $_POST[name] . '<input type=hidden name=name value="' . $_POST[name] . '">';

print '@';

$sql = "select id, name from domains where mail = 'on'";

if($uid) $sql .= " and uid = '$uid'";
if($did) $sql .= " and id = '$did'";
$result = mysql_query($sql);

$num = mysql_num_rows($result);

if($page_type == "add" and !$did) print "<select name=did>";
else print "<input type=hidden name=did value=$did>";

// If we're adding an email, print out a dropdown of domains

if( $page_type == "add" and !$did) {

  while ( $row = mysql_fetch_array($result) ) {

    print "<option value=$row[id]";
    if($row[id] == $did) print " selected";
    print ">"; 
    
    print $row[name] . '</option>';
    
  }

} else {
  
  // normail edit just prints the domain name

  $row = mysql_fetch_array($result);
  
  print $row[name];

}

if($page_type == "add" and !$did) print "</select>";

?></td></tr>
<tr><td>Password:</td><td><input type="password" name=passwd<?php

if($_POST[passwd]) print ' value="' . $_POST[passwd] . '"'; else print ' value="' . $row_mail[passwd] . '"';

?>></td></tr>
<tr><td>Confirm:</td><td><input type="password" name=confirm_passwd<?php

if($_POST[confirm_passwd]) print ' value="' . $_POST[confirm_passwd] . '"'; else print ' value="' . $row_mail[passwd] . '"';

?>></td></tr>
<tr><td>Mailbox:</td><td><input type="checkbox" name=mailbox value="true"<?php

if($page_type == "add") {
  
  if(!$action or $_POST[mailbox]) print ' checked';

} else {

  if($row_mail[mailbox] == "true") print ' checked';

}

?> onclick="if(!this.checked) return confirm('Mail will not be stored on the server if you disable this option. Are you sure you wish to do this?');"></td></tr>
<!-- per user spam-assassin enabling is currently disabled
<tr><td>SPAM Filter:</td><td><input type="checkbox" name=spamassassin value="true"<?php

if($page_type == "add") {

  if(!$action or $_POST[spamassassin]) print ' checked';

} else {

  if($row_mail[spamassassin] == "true") print ' checked';

}

?> onclick="if(!this.checked) return confirm('Are you sure you don\'t want spam filtering?');"></td></tr>-->
<tr><td valign="top">Redirect:</td><td valign="top">
<table style="border: 0px; margin: 0px;"><tr>
<td valign="top" align="left"><input type="checkbox" name=redirect value="true"<?php

if($page_type == "add") {

  if($_POST[redirect]) print ' checked';

} else {

  if($row_mail[redirect] == "true") print ' checked';

}

?> onclick="if(this.checked) { document.main.redirect_addr.disabled=false; if(tmp) document.main.redirect_addr.value=tmp; document.main.redirect_addr.focus(); } else { document.main.redirect_addr.disabled=true; tmp = document.main.redirect_addr.value; document.main.redirect_addr.value=''}"></td><td><font size="1">List email addresses here, seperate each with a comma and a space</font><br /><textarea nowrap rows="5" cols="40"<?php

if($page_type == "add") {

  if(!$_POST[redirect]) print ' disabled';

} else {

  if(!$row_mail[redirect]) print ' disabled';

}

?> name=redirect_addr><?php

if($page_type == "add") {

  if($_POST[redirect_addr]) print ereg_replace(',',', ',$_POST[redirect_addr]);

} else {

  if($row_mail[redirect_addr]) print ereg_replace(',',', ',$row_mail[redirect_addr]);

}

?></textarea></td></tr></table>
</td></tr>
<tr><td colspan=2><input type="hidden" name="action" value="add">
<?php if($page_type == "add") print '<input type="hidden" name="page_type" value="' . $page_type . '">'; ?>
<input type="submit" value="<?php 

if($page_type == "add") print 'Add Mail';
else print 'Update';

?>"></td></tr>
</table>

</form>

<?php

if($select) print '<script type="text/javascript">document.main.' . $select . '.focus()</script>';

nav_bottom();

?>
