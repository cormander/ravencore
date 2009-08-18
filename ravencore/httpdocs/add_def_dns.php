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

if ($action == "add") {
	$recs = $db->run("get_default_dns_recs");

	$found = 0;

	foreach ($recs as $rec) {
		if ($_POST[name] == $rec[name] and $_POST[type] == $rec[type] and $_POST[target] == $rec[target]) $found = 1;
	}

	if (0 != $found) {
		alert(__("You already have a $_POST[type] record for $_POST[name] pointing to $_POST[target]"));
	} else {
		if ($_POST[name] == $_POST[target] and $_POST[type] != "MX") {
			alert(__("Your record name and target cannot be the same."));
		} else {
			if (($_POST[type] == "SOA" or $_POST[type] == "MX" or $_POST[type] == "CNAME")
				and is_ip($_POST[target])) {
				alert(__("A $_POST[type] record cannot point to an IP address!"));
			} else {
				if (ereg('\.$', $_POST[name])) {
					alert(__("You cannot enter in a full domain as the record name."));
				} else {
					if ($_POST[type] == "MX") $_POST[type] .= '-' . $_POST[preference];

					$sql = "insert into dns_def set name = '$_POST[name]', type = '$_POST[type]', target = '$_POST[target]'";

					$db->data_query($sql);

					goto("dns_def.php");
				}
			}
		}
	}
}

nav_top();

print '<form method=post>
<input type=hidden name=action value=add>
';

switch ($_POST[type]) {
	case "SOA":

		$sql = "select count(*) as count from dns_def where type = 'SOA'";
		$result = $db->data_query($sql);

	$row = $db->data_fetch_array($result);

		if ($row['count'] != 0)
		{
			print __('You already have a default SOA record set');

			nav_bottom();

		}

		print '<input type=hidden name=type value=SOA>
' . __('Default Start of Authority') . ': <input type=text name=target>
';
		break;

	case "A":
		print '<input type=hidden name=type value=A>
			' . __('Record Name') . ': <input type=text name=name>
			<br>
			' . __('Target IP') . ': <input type=text name=target>
			';

		break;

	case "NS":
		print '<input type=hidden name=type value=NS>
			<input type=hidden name=name value="@">
			' . __('Nameserver') . ': <input type=text name=target>
			';
		break;

	case "MX":
		print __('Mail for the domain') . ': <input type=hidden name=name value="@">
	<input type=hidden name=type value=MX>
	' . __('MX Preference') . ': <select name=preference>';
		for($i = 10; $i < 51; $i += 10) print '<option value="' . $i . '">' . $i . '</option>';
		print '</select>
	<br>
	' . __('Mail Server') . ': <input type=text name=target> ( must not be an IP! )
	';
		break;

	case "CNAME":
		print '<input type=hidden name=type value=CNAME> ' . __('Alias name') . ': <input type=text name=name>
		<br>
		' . __('Target name') . ': <input type=text name=target>';
		break;

	case "PTR":
		print '<input type=hidden name=type value=PTR> ' . __('Reverse pointer records are not yet available');

		nav_bottom();

		break;

	default:
		print __('Invalid DNS record type');

		nav_bottom();

		break;
}

print '<p><input type=submit value="' . __('Add Record') . '">
</form>';

nav_bottom();

?>
