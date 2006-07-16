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

if ($action)
{ 
    // form sanity checks
    if (!$_POST[name])
    {
        alert(__("You must enter a name for this user"));
        $select = "name";
    } 
    else if (is_admin() and ! $_POST[login])
    {
        alert(__("You must enter a login for this user"));
        $select = "login";
    } 
    else if (!$_POST[passwd])
    {
        alert(__("You must enter a password for this user"));
        $select = "passwd";
    } 
    else if ($_POST[passwd] != $_POST[confirm_passwd])
    {
        alert(__("Your passwords do not match"));
        $_POST[passwd] = "";
        $_POST[confirm_passwd] = "";
        $select = "passwd";

        /*
  } else if($_POST[passwd] != $row_user[passwd]) {

    alert("Incorrect password. Information not updated.");
    $_POST[passwd] = "";
    $_POST[confirm_passwd] = "";
    $select = "passwd";
    */
    } 
    else if (!valid_passwd($_POST[passwd]))
    {
        alert(__("Your password must be atleast 5 characters long, and not a dictionary word."));
        $_POST[passwd] = "";
        $_POST[confirm_passwd] = "";
        $select = "passwd";
    } 
    else if (!$_POST[email] or !preg_match('/^' . REGEX_MAIL_NAME . '@' . REGEX_DOMAIN_NAME . '$/', $_POST[email]))
    {
        alert(__("The email address entered is invalid"));
        $_POST[email] = "";
        $select = "email";
    } 
    else if ($_POST[login] == $CONF[MYSQL_ADMIN_USER])
    {
        alert(__("$_POST[login] is an invalid login name"));
        $_POST[login] = "";
        $select = "login";
    } 
    else
    {
        if ($action == "add")
        { 
            // only an admin can add a user
            req_admin(); 
            // The procedue to add a user. First check to see if the login provided is already in use
            $sql = "select count(*) as count from users where login = '$_POST[login]'";
            $result = $db->data_query($sql);
	    $row = $db->data_fetch_array($result);

            if ($row[count] != 0)
            {
                alert(__("The user login '$_POST[login]' already exists")); 
                // Unset the login variable, so that we don't print it in the form below
                $_POST[login] = "";
                $select = "login"; 
                // Each of these checks provides the $select variable, which tells the page to focus on that
                // form element when the page loads.
            } 
            else
            { 
                // Create the user
                $sql = "insert into users set created = now(), name = '$_POST[name]', email = '$_POST[email]', login = '$_POST[login]', passwd = '$_POST[passwd]'";
                $db->data_query($sql); 
                // Grab the new user's ID number, so we can be sent to the next logical page
                $uid = $db->data_insert_id(); 
                // We either go to permissions setup, or to the user display of this new user
                if ($_POST[permissions]) goto("user_permissions.php?uid=$uid");
                else goto("users.php?uid=$uid");
            } 
        } 
        else if ($action == "edit")
        {
            $sql = "select count(*) as count from users where id != '$uid' and login = '$login'";
            $result = $db->data_query($sql);

	    $row = $db->data_fetch_array($result);

            if ($row[count] != 0) alert(__("The user login '$_POST[login]' already exists"));
            else
            {
                $sql = "update users set name = '$_POST[name]', email = '$_POST[email]', passwd = '$_POST[passwd]' " . ( is_admin() ? ", login = '$_POST[login]'" : "" ). " where id = '$uid'";
                $db->data_query($sql);

                goto("users.php?uid=$uid");
            } 
        } 
    } 
} 

nav_top();

if ($uid)
{
    $sql = "select * from users where id = '$uid'";
    $result = $db->data_query($sql);

    $row_u = $db->data_fetch_array($result);
} 
// In this form, we print values for the input fields only if we get them as post or database variables.

?>

<form name="main" method="post">

<table>
<tr><th colspan="2"><?php

print ($uid ? __('Edit') : __('Add')) . ' ' . __('info');

?></th></tr>
<tr><td>*<?php e_('Full Name')?>: </td><td><input type="text" name="name" value="<?php if ($_POST[name]) print $_POST[name];
else print $row_u[name];
?>"></td></tr>
<tr><td>*<?php e_('Email Address')?>: </td><td><input type="text" name="email" value="<?php if ($_POST[email]) print $_POST[email];
else print $row_u[email];
?>"></td></tr>
<tr><td>*<?php e_('Login')?>: </td><td><?php

if(is_admin()) print '<input type="text" name="login" value="' . ( $_POST[login] ? $_POST[login] : $row_u[login]) . '"></td></tr>';
else print $row_u[login];

?>
<tr><td>*<?php e_('Password')?>: </td><td><input type="password" name="passwd" value="<?php if ($_POST[passwd]) print $_POST[passwd];
else/*if(is_admin())*/ print $row_u[passwd];
?>"></td></tr>
<tr><td>*<?php e_('Confirm')?>: </td><td><input type="password" name="confirm_passwd" value="<?php if ($_POST[confirm_passwd]) $_POST[passwd];
else/*if(is_admin())*/ print $row_u[passwd];
?>"></td></tr>
<tr><td colspan="2" align="right"><input type="hidden" name="action" value="<?php if (!$uid) print 'add';
else print 'edit';
?>">
<input type="submit" value="<?php if (!$uid) print __('Add User');
else print __('Edit Info');
?>">
<?php if (!$uid and is_admin()) print '<br /><input type=checkbox name=permissions value=true checked> ' . __('Proceed to Permissions Setup');
?>
</td></tr>
</table>

<p>
* <?php e_('Required fields')?>

</form>

<?php 
// only the admin can delete users
if (is_admin() and $uid) print '<p>&nbsp;<p><a href=users.php?action=delete&uid=' . $uid . ' onclick="return confirm(\'' . __('Are you sure you wish to delete this user?') . '\');">' . __('delete') . '</a>';
// Use javascript to focus on the selected element, if there is one
if ($select) print '<script type="text/javascript">document.main.' . $select . '.focus()</script>';

nav_bottom();

?>