<?php

include "auth.php";

if($action) {

  //form sanity checks

  if(!$_POST[name]) {

    alert("You must enter a name for this user");
    $select = "name";

  } else if(!$_POST[login]) {
    
    alert("You must enter a login for this user");
    $select = "login";
    
  } else if(!$_POST[passwd]) {

    alert("You must enter a password for this user");
    $select = "passwd";

  } else if($_POST[passwd] != $_POST[confirm_passwd]) {

    alert("Your passwords do not match");
    $_POST[passwd] = "";
    $_POST[confirm_passwd] = "";
    $select = "passwd";

  } else if(!$_POST[email] or !preg_match('/^'.REGEX_MAIL_NAME.'@'.REGEX_DOMAIN_NAME.'$/',$_POST[email])) {

    alert("The email address entered is invalid");
    $_POST[email] = "";
    $select = "email";

  } else if($_POST[login] == $CONF[MYSQL_ADMIN_USER]) {

    alert("$_POST[login] is an invalid login name");
    $_POST[login] = "";
    $select = "login";

  } else {
  
    if($action == "add") {
      
      //only an admin can add a user
      req_admin();

      // The procedue to add a user. First check to see if the login provided is already in use
      
      $sql = "select count(*) as count from users where login = '$_POST[login]'";
      $result = mysql_query($sql);
      $row = mysql_fetch_array($result);
      
      if($row[count] != 0) {
	
	alert("The user login '$_POST[login]' already exists");
	
	// Unset the login variable, so that we don't print it in the form below
	
	$_POST[login] = "";
	$select = "login";
	
	// Each of these checks provides the $select variable, which tells the page to focus on that
	// form element when the page loads.
	
      } else {
	
	// Create the user
	
	$sql = "insert into users set created = now(), name = '$_POST[name]', email = '$_POST[email]', login = '$_POST[login]', passwd = '$_POST[passwd]'";
	mysql_query($sql);
	
	// Grab the new user's ID number, so we can be sent to the next logical page
	
	$uid = mysql_insert_id($link);
	
	// We either go to permissions setup, or to the user display of this new user
	
	if($_POST[permissions]) goto("user_permissions.php?uid=$uid");
	else goto("users.php?uid=$uid");
    
      }
      
    } else if($action == "edit") {

      $sql = "select count(*) as count from users where id != '$uid' and login = '$login'";
      $result = mysql_query($sql);
      
      $row = mysql_fetch_array($result);
      
      if($row[count] != 0) alert("The user login '$_POST[login]' already exists");
      else {
	
	$sql = "update users set name = '$_POST[name]', email = '$_POST[email]', login = '$_POST[login]', passwd = '$_POST[passwd]' where id = '$uid'";
	mysql_query($sql);

	goto("users.php?uid=$uid");
	
      }

    }

  }

}

nav_top();

if($uid) {

  $sql = "select * from users where id = '$uid'";
  $result = mysql_query($sql);

  $row_u = mysql_fetch_array($result);

}

// In this form, we print values for the input fields only if we get them as post or database variables.

?>

<form name="main" method="post">

<table>
<tr><th colspan="2"><?php

print ($uid ? 'Edit' : 'Add') . ' info';

?></th></tr>
<tr><td>*Full Name:</td><td><input type="text" name="name" value="<?php if($_POST[name]) print $_POST[name]; else print $row_u[name]; ?>"></td></tr>
<tr><td>*Email Address:</td><td><input type="text" name="email" value="<?php if($_POST[email]) print $_POST[email]; else print $row_u[email]; ?>"></td></tr>
<tr><td>*Login:</td><td><input type="text" name="login" value="<?php if($_POST[login]) print $_POST[login]; else print $row_u[login]; ?>"></td></tr>
<tr><td>*Password:</td><td><input type="password" name="passwd" value="<?php if($_POST[passwd]) print $_POST[passwd]; else print $row_u[passwd]; ?>"></td></tr>
<tr><td>*Confirm:</td><td><input type="password" name="confirm_passwd" value="<?php if($_POST[confirm_passwd]) $_POST[passwd]; else if(is_admin()) print $row_u[passwd]; ?>"></td></tr>
<tr><td colspan="2" align="right"><input type="hidden" name="action" value="<?php if(!$uid) print 'add'; else print 'edit'; ?>">
<input type="submit" value="<?php if(!$uid) print 'Add User'; else print 'Edit Info'; ?>">
<?php if(!$uid and is_admin()) print '<br /><input type=checkbox name=permissions value=true checked> Proceed to Permissions Setup'; ?>
</td></tr>
</table>

<p>
* Required fields

</form>

<?php

//only the admin can delete users
if(is_admin() and $uid) print '<p>&nbsp;<p><a href=users.php?action=delete&uid=' . $uid . ' onclick="return confirm(\'Are you sure you wish to delete this user?\');">delete</a>';

// Use javascript to focus on the selected element, if there is one

if($select) print '<script type="text/javascript">document.main.' . $select . '.focus()</script>';

nav_bottom();

?>