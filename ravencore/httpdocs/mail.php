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

if ($action == "update") {
	if (
	$_POST[catchall] == "send_to" and
	!preg_match('/^' . REGEX_MAIL_NAME . '@' . REGEX_DOMAIN_NAME . '$/', $_POST[catchall_addr])
	) {
	alert("Invalid email address for catchall");
	  }
	/*

Examples of allowable input for relay_host:

ravencore.com
ravencore.com:2525
[ravencore.com]
[ravencore.com]:2525
192.168.1.115
192.168.1.115:2525

A domain name in [ ] means force MX host lookup

	*/
	else if(
		$_POST[catchall] == "relay" and
		!preg_match('/^\[?' . REGEX_DOMAIN_NAME . '\]?(:\d*)?$/', $_POST[relay_host]) and
		!is_ip(preg_replace('/:\d*$/','',$_POST[relay_host]))
		) {
	alert("Relay to must be a hostname or IP address");
	  } else {
		$sql = "update domains set catchall = '$_POST[catchall]', catchall_addr = '$_POST[catchall_addr]', bounce_message = '$_POST[bounce_message]', relay_host = '$_POST[relay_host]', alias_addr = '$_POST[alias_addr]' where id = '$did'";

		$db->data_query($sql);

		if ($db->data_rows_affected()) $db->run("rehash_mail");

		goto("mail.php?did=$did");
	}
} else if ($action == "delete") {

  $d->delete_email($mid);

  goto("mail.php?did=$did");

} else if ($action == "toggle") {
	$sql = "update domains set mail = '$_POST[mail]' where id = '$did'";
	$db->data_query($sql);

	if ($db->data_rows_affected()) $db->run("rehash_mail");

	goto("mail.php?did=$did");
}

if ($did) {
	// we include the nav_top inside the if statement, because if we're a user and try to view the page
	// we'll go to the else statement and the req_admin will print out a nav_top
	nav_top();

	$domain = $db->run("get_domain_by_id", Array(id => $did));

	if (!is_array($domain)) print __("Domain does not exist");
	else {
		print '<form method=post name=main>' . __('Mail for') . ' <a href="domains.php?did=' . $domain[id] . '" onmouseover="show_help(\'' . __('Goto') . ' ' . $domain[name] . '\');" onmouseout="help_rst();">' . $domain[name] . '</a> ' . __('is') . ' ';

		if ($domain[mail] != "on") print __('OFF') . ' <a href="javascript:document.main.submit();" onmouseover="show_help(\'' . __('Turn ON mail for') . ' ' . $domain[name] . '\');" onmouseout="help_rst();">*</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=action value="toggle">
<input type=hidden name=mail value="on">
';
		else {
			print __('ON') . ' <a href="javascript:document.main.submit();" onmouseover="show_help(\'' . __('Turn OFF  mail for') . ' ' . $domain[name] . '\');" onmouseout="help_rst();" onclick="return confirm(\'' . __('Are you sure you wish to disable mail for this domain?') . '\');">*</a>
<input type=hidden name=did value="' . $did . '">
<input type=hidden name=action value="toggle">
<input type=hidden name=mail value="off">
</form>
<p>';

			print '<form method=POST>
' . __('Mail sent to email accounts not set up for this domain ( catchall address )') . ':
<br>
<input type=radio name=catchall value=send_to';
			if ($domain[catchall] == "send_to") print ' checked';
			print '> ' . __('Send to') . ': <input type=text name=catchall_addr value="' . $domain[catchall_addr] . '"> ';

			print '<br> <input type=radio name=catchall value=bounce';
			if ($domain[catchall] == "bounce") print ' checked';
			print '> ' . __('Bounce with') . ': <input type=text name=bounce_message value="' . $domain[bounce_message] . '"> <br>
<input type=radio name=catchall value=relay';
		if ($domain[catchall] == "relay") print ' checked';
		print '> ' . __('Relay to') . ': <input type=text name=relay_host value="' . $domain[relay_host] . '"> <br> ';

print '<input type=radio name="catchall" value="delete_it"';
			if ($domain[catchall] == "delete_it") print ' checked';
			print '> ' . __('Delete it') . ' <br>';

			$domains = $db->run("get_domains_by_user_id", Array(uid => $uid));

			// remove this $did and all ones with 'mail = no'
			for ($i = 0; $i < count($domains); $i++) {
				if ($did == $domains[$i][id] or "on" != $domains[$i][mail]) {
					array_splice($domains, $i, 1);
					$i--;
				}
			}

			// for domains with no user
			if (0 == count($domains)) {
				print '<input type=radio name=catchall value=alias_to';
				if ($domain[catchall] == "alias_to") print ' checked';
				print '> ' . __('Forward to that user') . ' @ <input type=text name=alias_addr value="' . $domain[alias_addr] . '">';
				// for users with more then one domain setup
			}
			else if (count($domains) > 0) {
				print '<input type=radio name=catchall value=alias_to';
				if ($domain[catchall] == "alias_to") print ' checked';
				print '> ' . __('Forward to that user') . ' @ <select name=alias_addr>';

				// all other domains for this user ( with mail turned on )
				foreach ($domains as $dom) {
					print '<option value="' . $dom[name] . '"';
					if ($domain[alias_addr] == $dom[name]) print ' selected';
					print '>' . $dom[name] . '</option>';
				}

				print '</select>';
			} else print '<input type=radio disabled> ' . __('You need at least one other domain in the account with mail turned on to be able to alias mail');
			print '<p>';

			print '
<input type=submit value="' . __('Update') . '"> <input type=hidden name=did value="' . $domain[id] . '"> <input type=hidden name=action value=update>
</form>
<p>';

			$mails = $db->run("get_mail_users_by_domain_id", Array(did => $domain[id]));

			if (0 == count($mails)) print __('No mail for this domain.') . '<p>';
			else print '<table class="listpad"><tr><th class="listpad" colspan="100%">' . __('Mail for this domain') . ':</th></tr>';

			foreach ($mails as $mail) {
				print '<tr>
				<td class="listpad"><a href="edit_mail.php?did=' .
				$mail[did] . '&mid=' . $mail[id] .
				'" onmouseover="show_help(\'' . __('Edit') . ' ' .
				$mail[mail_name] . '@' . $domain[name] .
				'\');" onmouseout="help_rst();">' . __('edit') . '</a></td>
				<td class="listpad">' . $mail[mail_name] . '@' . $domain[name] .
				'</td><td class="listpad">';

				print '&nbsp;</td>
				<td class="listpad"><a href=mail.php?did=' .
				$domain[id] . '&mid=' . $mail[id] .
				'&action=delete onmouseover="show_help(\'' . __('Delete') . ' ' .
				$mail[mail_name] . '@' . $domain[name] .
				'\');" onmouseout="help_rst();" onclick="';

				if (!user_can_add($uid, "email") and !is_admin()) print 'return confirm(\'' . __('If you delete this email, you may not be able to add it again.\rAre you sure you wish to do this?') . '\');';
				else print 'return confirm(\'' . __('Are you sure you wish to delete this email?') . '\');';
				print '">' . __('delete') . '</a></td></tr>';
			}

			if (0 != count($mails)) print '</table>';

			if (user_can_add($uid, "email") or is_admin()) {
				print ' <a href="edit_mail.php?did=' . $domain[id] . '"';

				if (!user_can_add($uid, "email") and is_admin()) print ' onclick="return confirm(\'' . __('This user is only allowed to create ' . user_have_permission($uid, "email") . ' email accounts. Are you sure you want to add another?') . '\');"';

				print ' onmouseover="show_help(\'' . __('Add an email account') . '\');" onmouseout="help_rst();">' . __('Add Mail') . '</a>';
			}
		}
	}
} else {
	// req_admin();
	nav_top();
	// check to see if we have any domains setup at all. If not, die with this error
	$sql = "select count(*) as count from domains";
	if ($uid) $sql .= " where uid = '$uid'";

	$result = $db->data_query($sql);

	$row = $db->data_fetch_array($result);

	if ($row[count] == 0) {
		print __('You have no domains setup.');
		// give an "add a domain" link if the user has permission to add one more
		if (is_admin() or user_have_permission($uid, "domain")) print ' <a href="edit_domain.php">' . __('Add a Domain') . '</a>';

		nav_bottom();

		exit;
	}

	if(user_can_add($uid, "email") or is_admin()) print '<a href="edit_mail.php" onmouseover="show_help(\'' . __('Create a new email account') . '\');" onmouseout="help_rst();">' . __('Add an email address') . '</a>';

	print '<p>
<form method="GET" name=search>
   ' . __('Search') . ': <input type=text name=search value="' . $_GET[search] . '">
<input type=submit value="' . __('Go') . '" onclick="if(!document.search.search.value) { alert(\'' . __('Please enter in a search value!') . '\'); return false; }">';

	if ($_GET[search]) print ' <input type=button value="' . __('Show All') . '" onclick="self.location=\'mail.php\'">';

	print '</form><p>';

	if (is_admin()) {
		// admins get to see all domains
		$sql = "select m.id as mid, d.id as did, m.mailbox, m.mail_name, d.name, m.passwd from mail_users m, domains d where did = d.id";
		if ($_GET[search]) $sql .= " and ( m.mail_name like '%$_GET[search]%' or d.name like '%$_GET[search]%' or concat(m.mail_name, '@', d.name) like '%$_GET[search]%' )";
		$sql .= " order by mail_name";
	} else {
		// users only get to look at their own, so we look in the users table as well
		$sql = "select m.id as mid, d.id as did, m.mailbox, m.mail_name, d.name, m.passwd from mail_users m, domains d, users u where did = d.id and d.uid = u.id and m.did = d.id and u.id = '$uid'";
		if ($_GET[search]) $sql .= " and ( m.mail_name like '%$_GET[search]%' or d.name like '%$_GET[search]%' or concat(m.mail_name, '@', d.name) like '%$_GET[search]%' )";
		$sql .= " order by mail_name";
	}

	$result = $db->data_query($sql);

	$num = $db->data_num_rows();

	if ($num == 0 and !$_GET[search]) print __("There are no mail users setup");
	else if ($_GET[search]) print __('Your search returned') . ' <i><b>' . $num . '</b></i> ' . __('results') . '<p>';

	if ($num != 0) print '<table class="listpad" width="45%"><tr><th class="listpad" colspan="100%">' . __('Email Addresses') . '</th></tr>';

	while ($row = $db->data_fetch_array($result)) {
		print '<tr><td class="listpad"><a href="edit_mail.php?did=' . $row[did] . '&mid=' . $row[mid] . '" onmouseover="show_help(\'' . __('Edit') . ' ' . $row[mail_name] . '@' . $row[name] . '\');" onmouseout="help_rst();">' . $row[mail_name] . '@' . $row[name] . '</td><td class="listpad">';

	if ( $row[mailbox] == "true" ) {
		//if (@fsockopen("127.0.0.1", 143)) print '<a href="webmail.php?mid=' . $row[mid] . '&did=' . $row[did] . '" target="_blank">' . __('Webmail') . '</a>';
		//else print '<a href="#" onclick="alert(\'' . __('Webmail is currently offline') . '\')" onmouseover="show_help(\'' . __('Webmail is currently offline') . '\');" onmouseout="help_rst();">' . __('Webmail') . ' ( ' . __('offline') . ' )</a>';
	  } else {
		print '&nbsp;';
	  }

	print '</td>
<td class="listpad"><a href=mail.php?did=' . $row[did] . '&mid=' . $row[mid] . '&action=delete onmouseover="show_help(\'' . __('Delete') . ' ' . $row[mail_name] . '@' . $row[name] . '\');" onmouseout="help_rst();" onclick="';

		if (!user_can_add($uid, "email") and !is_admin()) print 'return confirm(\'' . __('If you delete this email, you may not be able to add it again.\rAre you sure you wish to do this?') . '\');';
		else print 'return confirm(\'' . __('Are you sure you wish to delete this email?') . '\');';
		print '">' . __('delete') . '</a></td></tr>';
	}
}

if ($num != 0) print '</table>';

nav_bottom();

?>
