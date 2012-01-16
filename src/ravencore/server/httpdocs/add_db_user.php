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

if (!$dbid or !$did) send_to_url("users.php?user=$uid");

if ($action == "add") {
	// does user already exist?
	$ddbu = $db->run("get_database_user_by_name", Array(name => $_POST[login]));

	$count = ( $ddbu[id] ? 1 : 0 );

	// we can't use the admin username
	if ($_POST['login'] == $CONF['MYSQL_ADMIN_USER']) $count = 1;

	if (0 != $count) $_SESSION['status_mesg'] = $_POST[login] . __(" user already exists");
	else {
		if (preg_match('/^' . REGEX_PASSWORD . '$/', $_POST[passwd]) and valid_passwd($_POST[passwd])) {
			$dd = $db->run("get_database_by_id", Array(id => $dbid));

			$sql = "grant select,insert,update,delete,create,drop,alter on $dd[name].* to $_POST[login]@localhost identified by '$_POST[passwd]'";

			$db->data_query($sql);

			$sql = "insert into data_base_users set login = '$_POST[login]', db_id = '$dbid', passwd = '$_POST[passwd]'";
			$db->data_query($sql);

			send_to_url("databases.php?did=$did&dbid=$dbid");

		} else {
			alert(__("Invalid password. Must only contain letters and numbers, must be atleast 5 characters, and not a dictionary word"));
			$_POST[passwd] = "";
		}
	}
}

nav_top();

$dd = $db->run("get_database_by_id", Array(id => $dbid));

print __('Adding a user for database') . ' ' . $dd[name] . '<p>

<form method="post">

' . __('Login') . ': <input type="text" name=login>
<p>
' . __('Password') . ': <input type="password" name=passwd>
<p>

<input type="submit" value="' . __('Add User') . '">
<input type="hidden" name=action value="add">
<input type="hidden" name=did value="' . $did . '">
<input type="hidden" name=dbid value="' . $dbid . '">

</form>';

nav_bottom();

?>
