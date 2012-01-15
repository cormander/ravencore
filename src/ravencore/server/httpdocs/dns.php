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

req_service("dns");

if ($action == "delete") {
	if ($_POST['type'] == "SOA") {
		$sql = "update domains set soa = NULL where id = '$did'";
	} else {
		$sql = "delete from dns_rec where did = '$did' and id = '$_POST[delete]'";
	}

	$db->data_query($sql);

	$db->run("rehash_named");

	openfile("dns.php?did=$did");
}

if (!$did) {
	req_admin();

	nav_top();

	$found = 0;

	$domains = $db->run("get_domains");

	foreach ($domains as $domain) {
		if ($domain[soa]) $found = 1;
	}

	if (0 == $found) print __('No DNS records setup on the server');
	else {
		print __('The following domains are setup for DNS') . '<p>
<table class="listpad"><tr><th class="listpad">' . __('Domain') . '</th><th class="listpad">' . __('Records') . '</th></tr>';

		foreach ($domains as $domain) {
			if (!$domain[soa]) continue;

			print '<tr><td class="listpad"><a href="dns.php?did=' . $domain[id] . '">' . $domain[name] . '</a></td>';

			$recs = $db->run("get_dns_recs_by_domain_id", Array(did => $domain[id]));

			print '<td class="listpad" align=center>' . count($recs) . '</td></tr>';
		}

		print '</table>';
	}
} else {
	nav_top();

	$domain = $db->run("get_domain_by_id", Array(id => $did));

	if (!$domain[soa]) print '<form method=post action=add_dns.php name=main>
' . __('No SOA record setup for this domain') . ' - <a href="javascript:document.main.submit();">' . __('Add SOA record') . '</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=type value="SOA">
</form>';
	else {
		print __('DNS for') . ' <a href="domains.php?did=' . $did . '">' . $domain[name] . '</a><p>';

		print '<form method=post name=del>
' . __('Start of Authority for ') . $domain[name] . __(' is ') . $domain[soa] . ' - <a href="javascript:document.del.submit();">' . __('delete') . '</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=type value="SOA">
<input type=hidden name=action value=delete>
</form>
<p>';

		$recs = $db->run("get_dns_recs_by_domain_id", Array(did => $did));

		if (0 == count($recs)) print __("No DNS records setup for this domain");
		else {
			$found_a = 0;
			$found_ns = 0;

			foreach ($recs as $rec) {
				if ("A" == $rec[type]) $found_a = 1;
				if ("NS" == $rec[type]) $found_ns = 1;
			}

			// need to have both or else...
			if(0 == $found_a or 0 == $found_ns) print '<font color="red"><b>' . __("You need at least one A record and one NS record for your zone file to be created") . '</b></font>';

			print '<form method=post>';

			print '<table class="listpad"><tr><th class="listpad">&nbsp;</th><th class="listpad">' . __('Record Name') . '</th><th class="listpad">' . __('Record Type') . '</th><th class="listpad">' . __('Record Target') . '</th></tr>';

			foreach ($recs as $rec) {
				print '<tr><td class="listpad"><input type=radio name=delete value="' . $rec[id] . '"></td><td class="listpad">' . $rec[name] . '</td><td class="listpad">' . $rec[type] . '</td><td class="listpad">' . $rec[target] . '</td></tr>';
			}

			print '<tr><td class="listpad" colspan=4><input type=submit value="' . __('Delete Selected') . '"></tr>';

			print '<input type=hidden name=action value=delete>
<input type=hidden name=did value="' . $did . '">
</table></form>';
		}

		if (user_can_add($uid, "dns_rec") or is_admin()) print '<p><form method=post action=add_dns.php>
' . __('Add record') . ': <select name=type>
<option value=A>A</option>
<option value=NS>NS</option>
<option value=MX>MX</option>
<option value=CNAME>CNAME</option>
<option value=PTR>PTR</option>
<option value=TXT>TXT</option>
</select> <input type=submit value=' . __('Add') . '>
<input type=hidden name=did value="' . $did . '">
</form>';
	}
}

nav_bottom();

?>
