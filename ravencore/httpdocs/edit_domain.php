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
	$ret = $db->run("push_domain", Array(
		action => $action,
		name => $_POST[name],
		dns => $_POST[dns],
	));

	if ($ret) {
		$domain = $db->run("get_domain_by_name", Array(name => $_POST[name]));

		if ($_POST[hosting]) $url = "hosting.php?did=" . $domain[id];
		else $url = "domains.php?did=" . $domain[id];

		goto($url);
	}
}

nav_top();

?>

<form method="post">

<?php
// The admin user gets a dropdown of the users setup on the server, to assin
// to the domain
if (is_admin()) {
	$users = $db->run("get_users");

	if (0 != count($users)) {
		print __('Control Panel User') . ': <select name="uid"><option value="">' . __('Select One') . '</option>';

		foreach ($users as $user) {
			print "<option value=\"$user[id]\"";
			if ($uid == $user[id]) print " selected";
			print ">$user[name] - $user[login]</option>";
		}

		print '</select><p>';
	}
}

?>
<table>
<tr><th colspan="2"><?php e_('Add domain')?></th></tr>
<tr><td><?php e_('Name')?>: </td><td>http://<input type="text" name="name"></td></tr>
<tr><td align="center"><input type="submit" value="<?php e_('Add Domain')?>"></td>
<td><?php
// Only display these options if we are a webserver
if (have_service("web")) {
	?>
<input type=checkbox name=hosting value="true" <?php if (($action and $_POST[hosting]) or !$action) print ' checked';
	?>> <?php e_('Proceed to hosting setup')?>
<?php
}
// Only display this option if we have a DNS server in our cluster
if (have_service("dns")) {
	$dns = $db->run("get_default_dns_recs");

	if (0 != count($dns)) print '<input type=checkbox name="dns" value="true" checked> ' . __('Add default DNS to this domain');
}

?>
</td></tr>
</table>

<input type="hidden" name="action" value="add">
</form>

<?php

nav_bottom();

?>
