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

req_service("web");

if ($action) {
	$sql = "delete from domain_ips where did = " . $did;
	$db->data_query($sql);

	if(is_array($_POST['ip_addresses'])) {
		foreach ($_POST['ip_addresses'] as $key => $val) {
			$sql = "insert into domain_ips values ($did, '$val')";
			$db->data_query($sql);
		}
	}
}

if ($action == "edit") {
	if (!$did) goto("domains.php");

	$sql = "select u.id, login, passwd, shell from sys_users u, domains d where d.suid = u.id and d.id = '$did'";
	$result = $db->data_query($sql);

	$row = $db->data_fetch_array($result);

	// only do this if we got a passwd value that is different from the current passwd
	if ($_POST[passwd] != $row[passwd] or $_POST[login_shell] != $row[shell]) {
		// Make sure someone isn't trying to change the login shell w/o permission
		if (!user_can_add($uid, "shell_user") and !is_admin() and $row[shell] == $CONF[DEFAULT_LOGIN_SHELL]) $_POST[login_shell] = $CONF[DEFAULT_LOGIN_SHELL];

		$sql = "update sys_users set passwd = '$_POST[passwd]', shell = '$_POST[login_shell]' where id = '$row[id]'";
		$db->data_query($sql);

		$db->run("rehash_ftp", Array('login' => $row[login]));
	}

	$sql = "update domains set redirect_url = '$_POST[redirect_url]', www = '$_POST[www]', host_dir = '$_POST[dir]'";

	if (user_can_add($uid, "host_php") or is_admin() or $_POST[host_php] == "false") $sql .= ", host_php = '$_POST[php]'";
	if (user_can_add($uid, "host_cgi") or is_admin() or $_POST[host_cgi] == "false") $sql .= ", host_cgi = '$_POST[cgi]'";
	if (user_can_add($uid, "host_ssl") or is_admin() or $_POST[host_ssl] == "false") $sql .= ", host_ssl = '$_POST[ssl]'";

	$sql .= " where id = '$did'";
	$db->data_query($sql);
	// only mess with the filesystem if we affected the db
	if ($db->data_rows_affected()) $db->run("rehash_httpd", Array('name' => $d->name()));

	goto("domains.php?did=$did");
}
else if ($action == "add")
{
	// get sys_users setup in db
	$sql = "select count(*) as count from sys_users where login = '$_POST[login]'";
	$result = $db->data_query($sql);
	// open up our /etc/passwd file, and input only the usernames
	$handle = popen("cat /etc/passwd | awk -F : '{print $1}'", "r");

	while (!feof($handle)) $ftp_data .= fread($handle, 1024);

	pclose($handle);
	// put each username as a value in the sys_users array
	$sys_users = explode("\n", $ftp_data);
	// make sure we don't already have the user setup in the database or in the system
	// this prevents people from creating their ftp user as root or something
	if ($_POST[host_type] == "physical" and ($row[count] != 0 or in_array($_POST[login], $sys_users))) alert("You cannot create a FTP user with the login $_POST[login]!");
	else {
		if ($_POST[host_type] == "physical") {
			$sql = "insert into sys_users set login = '$_POST[login]', passwd = '$_POST[passwd]'";
			$db->data_query($sql);

			$suid = $db->data_insert_id();

			$sql = "update domains set suid = '$suid' where id = '$did'";
			$db->data_query($sql);
			// when the rehash_ftp is fixed, we want to run it with just the new username, rather then the --all switch
			$db->run("rehash_ftp");
		}

		$sql = "update domains set host_type = '$_POST[host_type]', redirect_url = '$_POST[redirect_url]', www = '$_POST[www]'";

		if (user_can_add($uid, "php") or is_admin()) $sql .= ", host_php = '$_POST[php]'";
		if (user_can_add($uid, "cgi") or is_admin()) $sql .= ", host_cgi = '$_POST[cgi]'";
		if (user_can_add($uid, "ssl") or is_admin()) $sql .= ", host_ssl = '$_POST[ssl]'";
		if (user_can_add($uid, "dir") or is_admin()) $sql .= ", host_dir = '$_POST[dir]'";

		$sql .= " where id = '$did'";

		$db->data_query($sql);

		// build httpd for this domain
		$db->run("rehash_httpd");

		// do logrotation for the domain
		$db->run("rehash_logrotate");

		goto("domains.php?did=$did");
	}
} else if ($action == "delete") {
  $d->delete_hosting();

  goto("domains.php?did=$did");
}

nav_top();

$domain = $db->run("get_domain_by_id", Array(id => $did));

if (!is_array($domain)) print __("Domain does not exist");
else {
	print '<form name=main method=POST>' . __('Name') . ': ' . $domain[name];

	if ($domain[host_type] != "none") print ' - <a href="hosting.php?action=delete&did=' . $domain[id] . '" onclick="return confirm(\'' . __('Are you sure you wish to delete hosting for this domain?') . '\');">' . __('delete hosting') . '</a>
<input type=hidden name=did value=' . $domain[id] . '>';

	print '<p>';

	if ($_POST[host_type] or $domain[host_type] != "none") {

 // array of possible IPs
	$ip_addresses = $db->run("get_ip_addresses");
	$ips = Array();

	for ($i = 0; $i < count($ip_addresses); $i++) {
		if (is_numeric($ip_addresses[$i][uid]) and $ip_addresses[$i][uid] == 0 or $ip_addresses[$i][uid] == $uid) {
			$ips[$ip_addresses[$i][ip_address]] = $ip_addresses[$i][ip_address];
		}
	}

  // array of currently used IPs
	$domain_ip_addresses = $db->run("get_ip_addresses_by_domain_id", Array(did => $domain[id]));
	$dips = Array();

	foreach ($domain_ip_addresses as $ip) {
		$dips[$ip[ip_address]] = $ip[ip_address];
	}

	// only give option if there are any possible IPs
	if (count($ip_addresses) != 0) {
		print __("IP Address") . ": " . selection_array("ip_addresses", $dips, -1, "MULTIPLE", $ips) . "<p>";
	}

	print __("www prefix") . ": <input type=radio name=www value=true";
	if ($domain[www] == "true") print " checked";
	print "> " . __('Yes') . " <input type=radio name=www value=false";
	if ($domain[www] == "false") print " checked";
	print "> " . __('No') . " <p>";

	}

	// If we get here and have a $host_type value, then we got here from the 'none' case, so we need
	// To populate our host_type with this value to use the switch correctly
	if ($_POST[host_type]) $domain[host_type] = $_POST[host_type];

	switch ($domain[host_type]) {
		case "physical":
			// If we have this value, we don't have ftp information. Print form to add it
			if ($_POST[host_type]) print '
' . __('FTP Username') . ': <input type=text name=login><p>
' . __('FTP Password') . ': <input type=password name=passwd>';

			else {
				// We cant edit the the FTP username w/o deleting hosting for the domain
				$sql = "select login, passwd, shell from sys_users u, domains d where u.id = d.suid and d.id = '$did'";
				$result_ftp = $db->data_query($sql);

		$row_ftp = $db->data_fetch_array($result_ftp);

				print __('FTP Username') . ': ' . $row_ftp[login] . '<p>';
				print __('FTP Password') . ': <input type=password name=passwd value="' . $row_ftp[passwd] . '">';
			}

			print '<p>' . __('Shell') . ': <select name=login_shell';
			if (!user_can_add($uid, "shell_user") and !is_admin() and $row_ftp[shell] == $CONF[DEFAULT_LOGIN_SHELL]) print " disabled";
			print '><option value="' . $CONF[DEFAULT_LOGIN_SHELL] . '">' . $CONF[DEFAULT_LOGIN_SHELL] . '</option>';

			$shell_arr[0] = "/bin/bash";
			$shell_arr[1] = "/bin/sh";

			foreach($shell_arr as $shell) {
				print '<option value="' . $shell . '"';
				if ($shell == $row_ftp[shell]) print ' selected';
				print '>' . $shell . '</option>';
			}

			print '</select></p>';

			print "<p>" . __('SSL Support') . ": <input type=radio name=ssl value=true";
			if ($domain[host_ssl] == "true") print " checked";
			if (!user_can_add($uid, "host_ssl") and !is_admin()) print " disabled";
			print "> " . __('Yes') . " <input type=radio name=ssl value=false";
			if ($domain[host_ssl] == "true" and !user_can_add($uid, "host_ssl") and !is_admin()) print ' onclick="return confirm(\'' . __('If you disable ssl support, you will not be able to enable it again.\rAre you sure you wish to do this?') . '\');"';
			if ($domain[host_ssl] == "false") print " checked";
			print "> " . __('No');

			print "<p>" . __('PHP Support') . ": <input type=radio name=php value=true";
			if ($domain[host_php] == "true") print " checked";
			if (!user_can_add($uid, "host_php") and !is_admin()) print " disabled";
			print "> " . __('Yes') . " <input type=radio name=php value=false";
			if ($domain[host_php] == "true" and !user_can_add($uid, "host_php") and !is_admin()) print ' onclick="return confirm(\'' . __('If you disable php support, you will not be able to enable it again.\rAre you sure you wish to do this?') . '\');"';
			if ($domain[host_php] == "false") print " checked";
			print "> " . __('No');

			print "<p>" . __('CGI Support') . ": <input type=radio name=cgi value=true";
			if ($domain[host_cgi] == "true") print " checked";
			if (!user_can_add($uid, "host_cgi") and !is_admin()) print " disabled";
			print "> " . __('Yes') . " <input type=radio name=cgi value=false";
			if ($domain[host_cgi] == "true" and !user_can_add($uid, "host_cgi") and !is_admin()) print ' onclick="return confirm(\'' . __('If you disable cgi support, you will not be able to enable it again.\rAre you sure you wish to do this?') . '\');"';
			if ($domain[host_cgi] == "false") print " checked";
			print "> " . __('No');

			print '<p>' . __('Directory indexing') . ': <input type=radio name=dir value=true';
			if ($domain[host_dir] == "true") print ' checked';
			print '> ' . __('Yes') . ' <input type=radio name=dir value=false';
			if ($domain[host_dir] == "false") print ' checked';
			print '> ' . __('No') . '<p>';
			// If we have the $host_type value here, it means we need to add it rather then update it
			if ($_POST[host_type]) print '<input type=submit value=' . __('Add') . '> <input type=hidden name=action value=add>';
			else print '<input type=submit value=' . __('Update') . '> <input type=hidden name=action value=edit>';
			print '<input type=hidden name=host_type value=physical>';

			break;

		case "redirect":

			print '<input type=text name=redirect_url value="';
			if ($domain[redirect_url]) print $domain[redirect_url];
			else print 'http://';
			print '"><p>';
			// If we have the $host_type value here, it means we need to add it rather then update it
			if ($_POST[host_type]) print '<input type=submit value=' . __('Add') . '> <input type=hidden name=action value=add>';
			else print '<input type=submit value=' . __('Update') . '> <input type=hidden name=action value=edit>';
			print '<input type=hidden name=host_type value=redirect>';

			break;

		case "alias":

			print __('This domain is an alias of') . ' <select name=redirect_url>';

			foreach($u->info['domains'] as $dom_id) {

				// we can't have an alias to ourself
				if($dom_id == $did) continue;

				$d = new domain($dom_id);

				print '<option value="' . $d->name() . '"' . ($domain[redirect_url] == $d->name() ? ' selected' : '' ) . '>' . $d->name() . '</option>';

				if ($domain[redirect_url] == $d->name()) print ' selected';

			}

			print '"><p>';
			// If we have the $host_type value here, it means we need to add it rather then update it
			if ($_POST[host_type]) print '<input type=submit value=' . __('Add') . '> <input type=hidden name=action value=add>';
			else print '<input type=submit value=' . __('Update') . '> <input type=hidden name=action value=edit>';
			print '<input type=hidden name=host_type value=alias>';

			break;

		default:

			print '<input type=radio name=host_type value=physical> ' . __('Host on this server') . '
<br>
<input type=radio name=host_type value=redirect> ' . __('Redirect to another domain') . '
<br>
<input type=radio name=host_type value=alias> ' . __('Show contents of another site on this server') . '
<p>
<input type=submit value="' . __('Continue') . '">';
			break;
	}

	print '</form>';
}

nav_bottom();

?>
