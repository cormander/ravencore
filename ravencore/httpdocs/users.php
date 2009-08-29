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

include("auth.php");

if ($action) {
	$ret = $db->run("push_user", Array(
		action => $action,
		uid => $uid,
		login => $_REQUEST[login],
	));

	if ($ret) goto("users.php" . ("delete" == $action ? "" : "?uid=$uid"));
}

nav_top();


// We don't have to worry about checking if we're an admin here, because
// the $uid variable will always be set if we're a user
if (!$uid) {

	$users = $db->run("get_users");

	$num = count($users);

	if ($num == 0) print __('There are no users setup');
	else {
		print '<table class="listpad"><tr>
<th class="listpad">' . __('Name') . '</th>
<th class="listpad">' . __('Domains') . '</th>
<th class="listpad">' . __('Space usage') . '</th>
<th class="listpad">' . __('Traffic usage') . '</th>
</tr>';
		// set our totals to zero
		$total_space = 0;
		$total_traffic = 0;

		foreach ($users as $user) {

			$space = $user[space_usage];
			$traffic = $user[traffic_usage];
			$domains = $user[num_domains];

			// add to our totals
			$total_space += $space;
			$total_traffic += $traffic;
			$total_domains += $domains;

			print '<tr><td class="listpad"><a href="users.php?uid=' .
				$user[id] . '" onmouseover="show_help(\' ' . __('View user data for') . ' ' .
				$user[name] . '\');" onmouseout="help_rst();">' .
				$user[name] . '</a></td><td class="listpad" align=right>' .
				$domains . '</td><td class="listpad" align=right>' .
				$space . ' MB</td><td class="listpad" align=right>' .
				$traffic . ' MB</td></tr>';
		}

		print '<tr><td class="listpad">' . __('Totals') . '</td></td><td class="listpad" align=right>' . $total_domains . '</td><td class="listpad" align=right>' . $total_space . ' MB</td><td class="listpad" align=right>' . $total_traffic . ' MB</td></tr></table>';
	}

	print '<p><a href="edit_user.php" onmouseover="show_help(\' ' . __('Add a user to the control panel') . '\');" onmouseout="help_rst();">' . __('Add a Control Panel user') . '</a>';
}
else
{
	$user = $db->run("get_user_by_id", Array( id => $uid));

	if (!$user) {
		print __('User does not exist');
	} else {

		// if they are locked out, provide a way to unlock it
		if (is_admin() and $db->run("get_login_failure_count_by_username", Array(username => $user[login])) >= $CONF['LOCKOUT_COUNT']) print '<font color="red"><b>' . __('This user is locked out due to failed login attempts') . '</b></font> - <a href="users.php?action=unlock&login=' . $user[login] . '&uid=' . $user[id] . '">' . __('Unlock') . '</a>';

		print '
            <table class="listpad" width="45%" style="float: left">
                <tr>
         <th colspan=2 class="listpad">' . __('Info for') . ' <strong>' . $user[name] . '</strong></th>
                </tr>
                <tr>';

        print '<td class="listpad" valign="top">' . __('Company') . ':</td>
	 <td class="listpad" valign="top">' . $user[company] . '&nbsp;</td>
                </tr>
                <tr>';

        print '<td class="listpad" valign="top">' . __('Created') . ':</td>
	 <td class="listpad" valign="top">' . $user[created] . '&nbsp;</td>
                </tr>
                <tr>
	 <td class="listpad" valign="top">' . __('Contact email') . ':</td>
	 <td class="listpad" valign="top">' . $user[email] . '&nbsp;</td>
	        </tr>
                <tr>
	 <td class="listpad" valign="top">' . __('Login ID') . ':</td>
	 <td class="listpad" valign="top">' . $user[login] . '&nbsp;</td>
                </tr>
                <tr>
	 <td class="listpad" colspan=2 valign="top">&nbsp;</td>
                </tr>
                <tr>
	 <td class="listpad" valign="top"><a href="edit_user.php';
		// only the admin can see the uid
		if (is_admin()) print '?uid=' . $user[id] . '';

		print '" onmouseover="show_help(\'' . __('Edit account info') . '  \');" onmouseout="help_rst();">' . __('Edit account info') . '</a></td>
		 <td class="listpad" valign="top"><a href="user_permissions.php';
		// the admin sees the uid on this link
		if (is_admin()) print '?uid=' . $uid;

		print '" onmouseover="show_help(\'' . __('See what you can and can not do') . ' \');" onmouseout="help_rst();">';

		if (is_admin()) print __('View/Edit Permissions');
		else print __('View Permissions');

		print '</a></td>
                </tr>
            </table>

	    <table class="listpad" width="45%" style="float: right">
  	        <tr><th class="listpad">' . __('Options') . '</th></tr>
                <tr>
	 <td class="listpad" valign="top" width="50%">

<script type="text/javascript">

function sel_toggle(a) {

document.main.action=a

didsel.style.visibility=\'visible\'
mainmenu.style.visibility=\'hidden\'

}

function men_toggle() {

didsel.style.visibility=\'hidden\'
mainmenu.style.visibility=\'visible\'

}

</script>
';
		$domains = $db->run("get_domains_by_user_id", Array(uid => $uid));

		if (0 == count($domains)) {
			// users will see a different message here than the admin
			if (!is_admin()) print __('You have no domains setup');
			else print __('No domains setup');
			print '<p>';
		} else {
			print '<form name=main><div id="didsel" style="visibility: hidden;">
<select name="did" onchange="if(did.value!=0) document.main.submit();"><option value=0>' . __('For which domain') . '?</option>';

			foreach ($domains as $domain) {
				print '<option value="' . $domain[id] . '">' . $domain[name] . '</option>';
			}

			if (0 != count($domains)) print '</select> <input type=submit value=Go><br><a href="#" onclick="men_toggle();">' . __('Back') . '</a></div></form>';

			print '<div id="mainmenu">';

			if (have_service("mysql")) print '<p>
<a href="#" onmouseover="show_help(\'' . __('Add a MySQL database') . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'add_db.php\');">' . __('Add a MySQL database') . '</a><p>';

			if (have_service("mail")) print '<a href="#" onmouseover="show_help(\'' . __('Add E-Mail Account') . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'edit_mail.php?page_type=add\');">' . __('Add E-Mail Account') . '</a><p>';

			if (have_service("dns")) print '<a href="#" onmouseover="show_help(\'' . __('Add/Edit DNS records') . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'dns.php\');">' . __('Add/Edit DNS records') . '</a><p>';

			if (have_service("web")) print '<a href="#" onmouseover="show_help(\'' . __('View Webstatistics') . '\');" onmouseout="help_rst();" onclick="sel_toggle(\'webstats.php\');">' . __('View Webstatistics') . '</a><p>';
			// the admin user can see the uid here
			print '<a href="domains.php';
			if (is_admin()) print '?uid=' . $uid;
			print '" onmouseover="show_help(\'' . __('List all of your domain names') . '\');" onmouseout="help_rst();">' . __('List Domains') . '</a><p>';
		}

		if (have_domain_services()) {
			// print the link to add a domain if the user has permissions to
			if (user_can_add($uid, "domain")) {
				print '<a href="edit_domain.php';
				if (is_admin()) print '?uid=' . $uid;
				print '" onmouseover="show_help(\'' . __('Add a domain to the server') . '\');" onmouseout="help_rst();">' . __('Add a Domain') . '</a>';
			} else {
				// users see a different message here than the admin
				if (!is_admin()) print '' . __('You are at your limit for the number of domains you can have') . '';
				else print '' . __('This user is at his/her domain limit') . ' - <a href="edit_domain.php?uid=' . $uid . '">' . __('Add one anyway') . '</a>';
			}
		}
		// close the div tag if we have more than 0 domains
		if (0 != count($domains)) print '</div>';

		print '
    </td>
            </tr>
            </table>

<table width="45%" style="float: left; margin-top: 10px" class="listpad">
<tr><th class="listpad" colspan=2>' . __('Domain usage') . '</th></tr>
<tr><td class="listpad">' . __('Space usage') . ':</td><td class="listpad" align=right>' . $user[space_usage] . ' MB</td></tr>
<tr><td class="listpad">' . __('Traffic usage (This month)') . ':</td><td class="listpad" lign=right>' . $user[traffic_usage] . ' MB</td></tr></table>';
    }
}

nav_bottom();

?>
