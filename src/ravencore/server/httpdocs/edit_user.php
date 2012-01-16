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

if ($action) {
	$ret = $db->run("push_user", Array(
		action => $action,
		name => $_POST[name],
		login => $_POST[login],
		passwd => $_POST[passwd],
		confirm_passwd => $_POST[confirm_passwd],
		email => $_POST[email],
		uid => $uid,
	));

	if (1 == $ret) {
		if (!$uid) {
			$user = $db->run("get_user_by_name", Array(username => $_POST[login]));
			$uid = $user[id];
		}

		if ($_POST[permissions]) send_to_url("user_permissions.php?uid=$uid");

		send_to_url("users.php?uid=$uid");
	}
}

nav_top();

if ($uid) $user = $db->run("get_user_by_id", Array(id => $uid));

// In this form, we print values for the input fields only if we get them as post or database variables.

?>

<form name="main" method="post">

<table>
<tr><th colspan="2"><?php

print ($uid ? __('Edit') : __('Add')) . ' ' . __('info');

?></th></tr>
<tr><td>*<?php e_('Full Name')?>: </td><td><input type="text" name="name" value="<?php if ($_POST[name]) print $_POST[name];
else print $user[name];
?>"></td></tr>
<tr><td>*<?php e_('Email Address')?>: </td><td><input type="text" name="email" value="<?php if ($_POST[email]) print $_POST[email];
else print $user[email];
?>"></td></tr>
<tr><td>*<?php e_('Login')?>: </td><td><?php

if(is_admin()) print '<input type="text" name="login" value="' . ( $_POST[login] ? $_POST[login] : $user[login]) . '"></td></tr>';
else print $user[login];

?>
<tr><td>*<?php e_('Password')?>: </td><td><input type="password" name="passwd" value="<?php if ($_POST[passwd]) print $_POST[passwd];
else/*if(is_admin())*/ print $user[passwd];
?>"></td></tr>
<tr><td>*<?php e_('Confirm')?>: </td><td><input type="password" name="confirm_passwd" value="<?php if ($_POST[confirm_passwd]) $_POST[passwd];
else/*if(is_admin())*/ print $user[passwd];
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
