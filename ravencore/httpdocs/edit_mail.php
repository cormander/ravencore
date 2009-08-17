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

$email_user_page = 1;

include "auth.php";

req_service("mail");
// if there iss't a mid, we assume add
if (!$mid) $page_type = "add";
// if there isn't a page type, we assume edit
if (!$page_type) $page_type = "edit";
// A mail user can't add another mail user
if ($page_type == "add" and $row_email_user) goto("edit_mail.php");

if (!user_can_add($uid, "email") and !is_admin() and $page_type == "add") goto("users.php?uid=$uid");

if ($action) {
	$sql = "select count(*) as count from mail_users where mail_name = '$_POST[name]' and did = '$did'";
	$result = $db->data_query($sql);
	$row = $db->data_fetch_array($result);

	if ($row[count] != 0 and $page_type == "add") alert(__("That email address already exists"));
	else {
		// Make sure that the passwords match
		if ($_POST[confirm_passwd] != $_POST[passwd]) {
			alert(__("Your passwords do not match"));
			$_POST[confirm_passwd] = "";
			$_POST[passwd] = "";
		} else {
			// Make sure the given mailname is valid
			if (preg_match('/^' . REGEX_MAIL_NAME . '$/', $_POST[name])) {
				if ($_POST[redirect] and !$_POST[redirect_addr]) {
					alert(__("You selected you wanted a redirect, but left the address blank"));
					$select = "redirect_addr";
				} else {
					$_POST[redirect_addr] = trim(ereg_replace(' ', '', $_POST[redirect_addr]));

					$redir_error = 0;

					if ($_POST[redirect_addr]) {
						$addrs = explode(',', $_POST[redirect_addr]);

						foreach($addrs as $email) {
							if (!preg_match('/^' . REGEX_MAIL_NAME . '@' . REGEX_DOMAIN_NAME . '$/', $email)) $redir_error = 1;
						}
					}

					if ($redir_error == 0) {
						if (preg_match('/^' . REGEX_PASSWORD . '$/', $_POST[passwd])) {
			  if ($page_type == "add") $sql = "insert into mail_users set mail_name = '$_POST[name]', did = '$did', passwd = '$_POST[passwd]', spam_folder = '$_POST[spam_folder]', mailbox = '$_POST[mailbox]', redirect = '$_POST[redirect]', redirect_addr = '$_POST[redirect_addr]', autoreply = '$_POST[autoreply]', autoreply_subject = '$_POST[autoreply_subject]', autoreply_body = '$_POST[autoreply_body]'";
			  else $sql = "update mail_users set passwd = '$_POST[passwd]', mailbox = '$_POST[mailbox]', spam_folder = '$_POST[spam_folder]', redirect = '$_POST[redirect]', redirect_addr = '$_POST[redirect_addr]', autoreply = '$_POST[autoreply]', autoreply_subject = '$_POST[autoreply_subject]', autoreply_body = '$_POST[autoreply_body]' where id = '$mid'";

							$db->data_query($sql);

							$db->run("rehash_mail");

							goto("mail.php?did=$did");
						} else {
							alert(__("Invalid password. Must only contain letters and numbers."));
							$_POST[passwd] = "";
							$select = "passwd";
						}
					} else {
						alert(__("The redirect list contains an invalid email address."));
						$_POST[redirect_addr] = "";
						$select = "redirect_addr";
					}
				}
			} else {
				// We failed to pass the mailname regex
				alert(__("Invalid mailname. It may only contain letters, number, dashes, dots, and underscores. Must both start and end with either a letter or number."));
				$_POST[name] = "";
				$select = "name";
			}
		}
	}
}

nav_top();

// check to make sure that mail is actually turned on for the $did, if we have one
if ($did) {
	$domain = $db->run("get_domain_by_id", Array(id => $did));

	if ("on" != $domain[mail]) {
		print __('Mail is disabled for ' . $row_chk[name] . '. You can not add an email address for it.');

		nav_bottom();

		exit;
	}
}

// Define the javascript variable "tmp" to blank
print '<script type="text/javascript"> var tmp=""</script>';

if ($page_type == "edit") {
	$mail = $db->run("get_mail_user_by_id", Array(id => $mid));

	$_POST[name] = $mail[mail_name];
}

?>

<form method="post" name="main">

<table>
<tr><th colspan="2"><?php

print ($mid ? __('Edit') : __('Add'));
print ' ' . __('mail');

?></th><th><?php

?></th></tr>

<tr><td><?php e_('Mail Name')?>: </td><td><?php

if ($page_type == "add") {

	?><input type="text" name="name"<?php if ($_POST[name]) print ' value="' . $_POST[name] . '"';
	?>><?php

} else print $_POST[name] . '<input type=hidden name=name value="' . $_POST[name] . '">';

print '@';

$domains = Array();

if (!$did and !$uid) $domains = $db->run("get_domains");
else if (!$did and $uid) $domains = $db->run("get_domains_by_user_id", Array(uid => $uid));
else if ($did) $domains[0] = $db->run("get_domain_by_id", Array(id => $did));

if ($page_type == "add" and !$did) print "<select name=did>";
else print "<input type=hidden name=did value=$did>";

// If we're adding an email, print out a dropdown of domains
if ($page_type == "add" and !$did) {
	foreach ($domains as $domain) {
		if ("on" != $domain[mail]) continue;
		print "<option value=$domain[id]";
		if ($domain[id] == $did) print " selected";
		print ">";

		print $domain[name] . '</option>';
	}
} else {
	print $domains[0][name];
}

if ($page_type == "add" and !$did) print "</select>";

?></td></tr>
<tr><td><?php e_('Password')?>: </td><td><input type="password" name=passwd<?php

if ($_POST[passwd]) print ' value="' . $_POST[passwd] . '"';
else print ' value="' . $mail[passwd] . '"';

?>></td></tr>
<tr><td><?php e_('Confirm')?>: </td><td><input type="password" name=confirm_passwd<?php

if ($_POST[confirm_passwd]) print ' value="' . $_POST[confirm_passwd] . '"';
else print ' value="' . $mail[passwd] . '"';

?>></td></tr>
<tr><td><?php e_('Mailbox')?>: </td><td><input type="checkbox" name=mailbox value="true"<?php

if ($page_type == "add") {
	if (!$action or $_POST[mailbox]) print ' checked';
} else {
	if ($mail[mailbox] == "true") print ' checked';
}

?> onclick="if(!this.checked) return confirm('<?php e_('Mail will not be stored on the server if you disable this option. Are you sure you wish to do this?')?>');"></td></tr>
<?php

if(have_service("amavisd")) {
?>

<tr><td>Spam Folder:</td><td><input type="checkbox" name="spam_folder" value="true"<?php

if ($page_type == "add") {
	if (!$action or $_POST['spam_folder']) print ' checked';
} else {
	if ($mail['spam_folder'] == "true") print ' checked';
}

?>></td></tr>
<?php
}
?>

<tr><td valign="top"><?php e_('Redirect')?>: </td><td valign="top">
<table style="border: 0px; margin: 0px;"><tr>
<td valign="top" align="left"><input type="checkbox" name=redirect value="true"<?php

if ($page_type == "add") {
	if ($_POST[redirect]) print ' checked';
} else {
	if ($mail[redirect] == "true" or $_POST[redirect]) print ' checked';
}

?> onclick="if(this.checked) { document.getElementById('redir').style.display=''; if(tmp) document.main.redirect_addr.value=tmp; document.main.redirect_addr.focus(); } else { document.getElementById('redir').style.display='none'; tmp = document.main.redirect_addr.value; document.main.redirect_addr.value=''}"></td><td><span style="display: <?php if($mail[redirect] != "true" and ! $_POST[redirect] ) print 'none'; ?>;" name="redir" id="redir"><font size="1"><?= __('List email addresses here, seperate each with a comma and a space')?></font><br /><textarea nowrap rows="5" cols="40" name=redirect_addr><?php

if ($page_type == "add") {
	if ($_POST[redirect_addr]) print ereg_replace(',', ', ', $_POST[redirect_addr]);
} else {
	if ($mail[redirect_addr]) print ereg_replace(',', ', ', $mail[redirect_addr]);
}

?></textarea></span></td></tr></table></td></tr>
<tr><td valign="top"><?= __('Auto-Responder')?>: </td><td valign="top">
<table style="border: 0px; margin: 0px;"><tr>
<td valign="top" align="left">
<?php
// only show autoreply if DBD::SQLite is installed
if($status['perl_modules']['DBD::SQLite']) {
?>

<input type="checkbox" name=autoreply value="1"<?php

if ($page_type == "add") {
	if ($_POST[autoreply]) print ' checked';
} else {
	if ($mail[autoreply] == 1 or $_POST[autoreply]) print ' checked';
}

?> onclick="if(this.checked) { document.getElementById('autore').style.display=''; document.main.autoreply_subject.focus(); } else { document.getElementById('autore').style.display='none'; document.main.autoreply_subject.value=''}"></td>
<td><span style="display: <?php if($mail[autoreply] != 1 and ! $_POST[autoreply] ) print 'none'; ?>;" name="autore" id="autore">
Subject: <input type="text" name="autoreply_subject" size="40" value="<?=( $_POST[autoreply_subject] ? $_POST[autoreply_subject] : $mail[autoreply_subject] )?>">
<br />
Message:<br/><textarea nowrap rows="5" cols="40" name=autoreply_body><?php

if ($page_type == "add") {
	if ($_POST[autoreply]) print ereg_replace(',', ', ', $_POST[autoreply_body]);
} else {
	if ($mail[autoreply]) print ereg_replace(',', ', ', $mail[autoreply_body]);
}

?></textarea></span>

<?php

} else {
	print '<font size="1"><i>The perl module DBD::SQLite needs to be installed</i></font>';

}

?>

</td></tr></table>

</td></tr>
<tr><td colspan=2><br/><input type="hidden" name="action" value="add">
<?php if ($page_type == "add") print '<input type="hidden" name="page_type" value="' . $page_type . '">';
?>
<input type="submit" value="<?php

if ($page_type == "add") print __('Add Mail');
else print __('Update');

?>"></td></tr>
</table>

</form>

<?php

if ($select) print '<script type="text/javascript">document.main.' . $select . '.focus()</script>';

nav_bottom();

?>
