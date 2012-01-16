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

if (!$did) send_to_url("domains.php");

if (!user_can_add($uid, "dns_rec") and !is_admin()) send_to_url("users.php?uid=$uid");

$domain = $db->run("get_domain_by_id", Array(id => $did));

if ($action == "add") {
	$ret = $db->run("push_dns_rec", Array(
		action => $action,
		did => $did,
		name => $_POST[name],
		type => $_POST[type],
		target => $_POST[target],
		preference => $_POST[preference]
	));

	if (1 == $ret)
		send_to_url("dns.php?did=$did");
}

if (0 == count($domain)) send_to_url("domains.php");

nav_top();

print '<form method=post>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=action value=add>
';

switch ($_POST[type]) {
	case "SOA":
		print '<input type=hidden name=type value=SOA>
' . __('Start of Authority for') . ' ' . $domain[name] . ': <input type=text name=target>
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
		print __('Mail for') . ': <select name=name>';

		$recs = $db->run("get_dns_recs_by_domain_id", Array(did => $did));

		foreach ($recs as $rec) {
			if ("A" != $rec[type]) continue;

			$disp_name = $rec[name];

			if ($rec[name] == "@") $disp_name = $domain[name];
			else $disp_name .= '.' . $domain[name];

			print '<option value="' . $rec[name] . '">' . $disp_name . '</option>';
		}

		print '</select><br><input type=hidden name=type value=MX>
' . __('MX Preference') . ': <select name=preference>';
		for($i = 10; $i < 51; $i += 10) print '<option value="' . $i . '">' . $i . '</option>';
		print '</select>
<br>
' . __('Mail Server') . ': <input type=text name=target> ( ' . __('must not be an IP!') . ' )
';
		break;
	case "CNAME":
		print '<input type=hidden name=type value=CNAME>
' . __('Alias name') . ': <input type=text name=name>
<br>
' . __('Target name') . ': <input type=text name=target>';
		break;
	case "TXT":
		print '<input type=hidden name=type value=TXT><input type=hidden name="name" value="@">
' . __('TXT') . ': <input type=text name=target>';
		break;
	case "PTR":
		print '<input type=hidden name=type value=PTR>
' . __('Reverse pointer records are not yet available');

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
