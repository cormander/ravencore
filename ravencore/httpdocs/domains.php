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

if($did) $domain_name = $d->name();

if ($action) {

	$uid = $d->info['uid'];

	switch ($action) {
		case "delete":
			if (is_admin()) $dest = "domains.php?uid=$uid";
			else $dest = "domains.php";
			break;
		case "hosting":
			$dest = basename($_SERVER['HTTP_REFERER']);
			break;
		case "change":
			// only an admin can do this
			if (!is_admin()) goto("users.php");
			break;
		default:
			// ????
			goto("users.php");
			break;
	}

	$ret = $db->run("push_domain", Array(
		action => $action,
		hosting => $_REQUEST[hosting],
		did => $did,
	));


	if (1 == $ret)
		goto($dest);

}

if (!$did) {
	nav_top();
	// print who the domains are for, if we're the admin and we're looking at a specific user's domains
	if ($uid and is_admin()) {
		$user = $db->run("get_user_by_id", Array(id => $uid));

		print '' . __('Domains for') . ' ' . $user[name] . '<p>';
	}

	if(is_admin()) print '<a href="edit_domain.php" onmouseover="show_help(\'' . __('Add a domain to the server') . '\');" onmouseout="help_rst();">' . __('Add a Domain') . '</a><p>';

	if (!is_admin() or $uid) $domains = $db->run("get_domains_by_user_id", Array(uid => $uid));
	else $domains = $db->run("get_domains");

	if (0 == count($domains)) {
		print __('There are no domains setup') . '.';
		// give an "add a domain" link if the user has permission to add one more
		if (is_admin() or user_have_permission($uid, "domain")) print ' <a href="edit_domain.php">' . __('Add a Domain') . '</a>';
	} else {
		print '<form method=get name=search>' . __('Search') . ': <input type=text name=search value="' . $_GET['search'] . '">
<input type=submit value=' . __('Go') . ' onclick="if(!document.search.search.value) { alert(\'' . __('Please enter a search value!') . '\'); return false; }">';

		if ($_GET['search']) print ' <input type=button value="' . __('Show All') . '" onclick="self.location=\'domains.php\'">';

		print '</form><p>';
	}

	if ($_GET['search']) print '' . __('Your search returned') . ' <i><b>' . count($domains) . '</b></i> ' . __('results') . '.<p>';

	if (0 != count($domains)) {

		$strContent = '<table class="listpad">
		<tr>
			<th class="listpad" style="width: 16px">' . __('Status') . '</th>
			<th class="listpad">' . __('Name') . '</th>
			<th class="listpad">' . __('Hosting') . '</th>
			<th class="listpad">' . __('Created') . '</th>
			<th class="listpad">' . __('Space usage') . '</th>
			<th class="listpad">' . __('Traffic usage') . '</th>
		</tr>';

		foreach ($domains as $domain) {
			$d = new domain($domain[id]);

			$space = $d->space_usage(date("m"), date("Y"));
			$traffic = $d->traffic_usage(date("m"), date("Y"));
			// add to our totals
			$total_space += $space;
			$total_traffic += $traffic;

			$helpMessage = '';

			switch ($domain[host_type]) {
				case "physical":
					$helpMessage = __('Physical hosting') . ': ';
					if ($domain[host_php]) $helpMessage .= __('PHP') . ' ';
					if ($domain[host_cgi]) $helpMessage .= __('CGI') . ' ';
					if ($domain[host_ssl]) $helpMessage .= __('SSL') . ' ';
					if ($domain[host_dir]) $helpMessage .= __('Directory indexing') . ' ';
					break;
				case "redirect":
					$helpMessage = __('Redirect to') . ' ' . $domain[redirect_url]  ;
					break;
				case "alias":
					$helpMessage = __('Alias of domain') . ' ' . $domain[redirect_url]  ;
					break;
				case "none":
					$helpMessage = __('No hosting');
					break;
			}

			if ($domain[hosting]=='on') {
				$strOnOffImage = '/images/solidgr.gif' ;
				$strOnOffHelpText = __('Hosting') . ' ' . __('Status') . ': ' . __('On');
				$strNewHosting = 'off';
			} else {
				$strOnOffImage = '/images/solidrd.gif';
				$strOnOffHelpText = __('Hosting') . ' ' . __('Status') . ': ' . __('Off');
				$strNewHosting = 'on';
			}

			$strContent .= '<tr>
				<td class="listpad" style="width: 16px; text-align: center" onmouseover="show_help(\'' . $strOnOffHelpText. '\');" onmouseout="help_rst();"><a href="domains.php?action=hosting&did='.$domain['id'].'&hosting='.$strNewHosting.'"><img src="'.$strOnOffImage.'" height="12" width="12" border="0"></a></td>
				<td class="listpad"><a href="domains.php?did=' . $domain['id'] . '" onmouseover="show_help(\'' . __('View setup information for') . ' ' . $domain['name'] . '\');" onmouseout="help_rst();">' . $domain['name'] . '</a></td>
				<td class="listpad" onmouseover="show_help(\'' . $helpMessage . '\');" onmouseout="help_rst();"><a href="hosting.php?did=' . $domain['id'] . '">' . $domain['host_type'] . '</a></td>
				<td class="listpad">' . $domain['created'] . '</td>
				<td class="listpad" align=right>' . $space . ' MB</td>
				<td class="listpad" align=right>' . $traffic . ' MB</td>
			</tr>';
		}

		$strContent .= '
		</table>

		';

		print $strContent;

    }
}  else {
	nav_top();

	$domain = $db->run("get_domain_by_id", Array(id => $did));

	if (!is_admin() and $domain[uid] != $uid) $domain = Array();

	if (0 == count($domain)) print __('Domain does not exist');
	else {

		if (is_admin()) {
			$uid = $domain[uid];

			print '<form method="post">' . __('This domain belongs to') . ': ' . selection_users($uid) . ' <input type=submit value="' . __('Change') . '">
<input type=hidden name=action value=change>
<input type=hidden name=did value="' . $did . '">
</form>';
		}

		print '<table class="listpad" width="45%" style="float: left">
<tr><th class="listpad" colspan="2">' . __('Info for') . ' ' . $domain[name] . '</th></tr>
<tr><td class="listpad">' . __('Name') . ':</td><td class="listpad">' . $domain[name] . ' - <a href="domains.php?action=delete&did=' . $domain[id] . '" onmouseover="show_help(\'' . __('Delete this domain off the server') . '\');" onmouseout="help_rst();" onclick="return confirm(\'' . __('Are you sure you wish to delete this domain') . '?\');">' . __('delete') . '</a></td></tr>';

		print '<tr><td class="listpad">' . __('Created') . ':</td><td class="listpad">' . $domain[created] . '</td></tr>';

		if (have_service("web")) {
			print '<tr><td class="listpad"><form method="post" name=status>' . __('Status') . ':</td><td class="listpad">';

			if ($domain[hosting] == "on") print '' . __('ON') . ' <a href="javascript:document.status.submit();" onclick="return confirm(\'' . __('Are you sure you wish to turn off hosting for this domain') . '?\');" onmouseover="show_help(\'' . __('Turn OFF hosting for this domain') . '\');" onmouseout="help_rst();">*</a><input type=hidden name=hosting value="off">';
			else print '' . __('OFF') . ' <a href="javascript:document.status.submit();" onmouseover="show_help(\'' . __('Turn ON hosting for this domain') . '\');" onmouseout="help_rst();">*</a><input type=hidden name=hosting value="on">';

			print '<input type=hidden name=did value=' . $did . '>
<input type=hidden name=action value=hosting>
</form></td></tr>
<tr><td class="listpad">';

			switch ($domain[host_type]) {
				case "physical":
					print '' . __('Physical Hosting') . ':</td><td class="listpad"><a href="hosting.php?did=' . $domain[id] . '" onmouseover="show_help(\'' . __('View/Edit Physical hosting for this domain') . '\');" onmouseout="help_rst();">' . __('edit') . '</a>';
					break;
				case "redirect":
					print '' . __('Redirect') . ':</td><td class="listpad"><a href="hosting.php?did=' . $domain[id] . '" onmouseover="show_help(\'' . __('View/Edit where this domain redirects to') . '\');" onmouseout="help_rst();">' . __('edit') . '</a>';
					break;
				case "alias":
					print '' . __('Alias of') . ' ' . $domain[redirect_url] . '</td><td class="listpad"> <a href="hosting.php?did=' . $row[id] . '" onmouseover="show_help(\'' . __('View/Edit what this domain is a server alias of') . '\');" onmouseout="help_rst();">' . __('edit') . '</a>';
					break;
				default:
					print '' . __('No Hosting') . ':</td><td class="listpad"><a href="hosting.php?did=' . $domain[id] . '" onmouseover="show_help(\'' . __('Setup hosting for this domain') . '\');" onmouseout="help_rst();">' . __('edit') . '</a>';
					break;
			}

			print '</td></tr></table>

<table class="listpad" width="45%" style="float: right">
<tr><th class="listpad" colspan=2>Options</th></tr>
<tr><td class="listpad">
';

			if ($domain[host_type] == "physical") {
				// the file manager make a connection to port 21 and uses FTP to manage files. If the ftp server is
				// offline, then we want to say that here.
				$ftp_working = @fsockopen("localhost", 21);

				if ($ftp_working) print '<a href="filemanager.php?did=' . $did . '" target="_blank" onmouseover="show_help(\'' . __('Go to the File Manager for this domain') . '\');" onmouseout="help_rst();">';
				else print '<a href="#" onclick="alert(\'' . __('The file manager is currently offline') . '\')" onmouseover="show_help(\'' . __('The file manager is currently offline') . '\');" onmouseout="help_rst();">';

				print __('File Manager');

				if (!$ftp_working) print ' ( ' . __('offline') . ' )';

				print '</a>';
				// log manager currently disabled, it broke somewhere along the line :)
				// print '<p><a href="log_manager.php?did=' . $did . '" onmouseover="show_help(\'' . __('Go to the Log Manager for this domain') . '\');" onmouseout="help_rst();">' . __('Log Manager') . '</a><p>';
			}

			if ($domain[host_type] == "physical") print '<p><a href="error_docs.php?did=' . $did . '" onmouseover="show_help(\'' . __('View/Edit Custom Error Documents for this domain') . '\');" onmouseout="help_rst();">' . __('Error Documents') . '</a></p>';
			else {
				$sql = "delete from error_docs where did = '$did'";
				$db->data_query($sql);
			}
		}

		if (have_service("mail")) {
			print '<p><a href="mail.php?did=' . $domain[id] . '" onmouseover="show_help(\'' . __('View/Edit Mail for this domain') . '\');" onmouseout="help_rst();">' . __('Mail') . '</a>';

			if ($domain[mail] == "on") {
				$mails = $db->run("get_mail_users_by_domain_id", Array(did => $domain[id]));
				print ' (' . count($mails) . ')';
			} else print __('( off )');

			print '</p>';
		}

		print '<a href="databases.php?did=' . $domain[id] . '" onmouseover="show_help(\'' . __('View/Edit databases for this domain') . '\');" onmouseout="help_rst();">' . __('Databases') . '</a>';

		$databases = $db->run("get_databases_by_domain_id", Array(did => $did));
		print ' (' . count($databases) . ')<p>';

		if (have_service("dns")) {
			print '<a href="dns.php?did=' . $did . '" onmouseover="show_help(\'' . __('Manage DNS for this domain') . '\');" onmouseout="help_rst();">' . __('DNS Records') . '</a>';

			if ($domain[soa]) {
				$rec = $db->run("get_dns_recs_by_domain_id", Array(did => $domain[id]));
				print ' (' . count($rec) . ')';
			} else print __('( off )');

			print '<p>';
		}

		if (have_service("web")) print '<a href="webstats.php?did=' . $domain[id] . '" target=_blank onmouseover="show_help(\'' . __('View Webstats for this domain') . '\');" onmouseout="help_rst();">' . __('Webstats') . '</a>';

		print '</td></tr></table>';

		if ($domain[host_type] == "physical") {
			print '<table class="listpad" width="45%" style="float: left;margin-top: 10px">
<tr><th class="listpad" colspan="2">' . __('Domain Usage') . '</th></tr>
<tr><td class="listpad">' . __('Disk space usage') . ': </td><td class="listpad">' . $d->space_usage(date("m"), date("Y")) . 'MB </td></tr>
<tr><td class="listpad">' . __('This month\'s bandwidth') . ': </td><td class="listpad">' . $d->traffic_usage(date("m"), date("Y")) . 'MB</td></tr></table>';
		}
	}
}

nav_bottom();

?>
