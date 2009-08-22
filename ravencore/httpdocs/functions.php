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

// get our locale functions
include "functions/locale.php";

// classes
include "classes/domain.php";
include "classes/user.php";

//

function rc_exit() {
	session_write_close();
	exit;
}

//
function update_parameter($type_id, $param, $value) {

	global $db;

	$sql = "delete form parameters where type_id = '$type_id' and param = '$param'";
	$db->data_query($sql);

	$sql = "insert into parameters set type_id = '$type_id', param = '$param', value = '$value'";
	$db->data_query($sql);
}

// a basic password validation function
function valid_passwd($passwd) {
	if (function_exists("pspell_new")) {
		// we use the english dictionary
		$d = pspell_new("en");
		// if the string is a word, it isn't a safe password
		if (pspell_check($d, $passwd)) return false;
	}

	// if the string is less than 5 characters long, it isn't a safe password
	if (strlen($passwd) < 5) return false;

	return true;
}

// A function to tell us if we have any "domain" services, or in other words,
// any service that requires the use of the domains table to function
function have_domain_services() {
	// domain services as of version 0.0.1
	if (have_service("web") or have_service("mail") or have_service("dns")) return true;

	return false;
}

// A function to tell us whether or not we have database services.
// Right now it only looks for mysql, but this will in the future look for other db types
// such as pgsql, mssql, etc

function have_database_services() {
	if (have_service("mysql")) return true;
	else return false;
}

function have_service($service) {
	global $status;

	if ($status['modules_enabled'][$service])
		return true;

	return false;
}

// A function that requires the existances of the webserver to load the page
// Must use before you output any headers.
function req_service($service) {
	if (!have_service($service)) {
		nav_top();

		print __('This server does not have ' . $service . ' installed. Page cannot be displayed.');

		nav_bottom();
	}
}

// A function to convert the number of bytes into K or MB
function readable_size($size) {
	if ($size > 1048576) {
		$size /= 1048576;
		$size = round($size, 2) . 'MB';
	} else if ($size = round(($size / 1024), 2)) $size .= 'K';

	return $size;
}

// A function to delete a domain's log file
function delete_log($did, $log_file) {
	$d = new domain($did);

	$domain_name = $d->name();

	$db->run("log_del", Array('name' => $domain_name, 'log_file' => $log_file));
}

// A function to tell us whether or not given string is an ip address. I got the core
// routines off of php.net, but made some of my own changes to make it return a bool value
function is_ip($ip) {
	$ip = trim($ip);
	if (strlen($ip) < 7) return false;
	if (!ereg("\.", $ip)) return false;
	if (!ereg("[0-9.]{" . strlen($ip) . "}", $ip)) return false;
	$ip_arr = split("\.", $ip);
	if (count($ip_arr) != 4) return false;

	for ($i = 0;$i < count($ip_arr);$i++) {
		if ((!is_numeric($ip_arr[$i])) || (($ip_arr[$i] < 0) || ($ip_arr[$i] > 255))) return false;
	}
	return true;
}

// A function to queue a message to be output with a javascript alert
// It must be called before the nav_top function
function alert($message) {
	global $js_alerts;

	array_push($js_alerts, $message);
}

// A function to do a header to the given location. Must be called before output
// goes to the browser. This function is used just about everywhere.
function goto($url) {
	global $db;

	// if we have a status_mesg, store it in the session
	if (!empty($db->status_mesg)) $_SESSION['status_mesg'] = $db->status_mesg;

	// session variables may not be saved before the browser changes to the new page, so we need to
	// save them here
	session_write_close();

	header("Location: $url");

	rc_exit();

}

// Returns the correct word assositated with a permission
function perm_into_word($perm) {
	switch ($perm) {
		case "domain":

			return "Domains";

		case "database":

			return "Databases";

		case "crontab":

			return "Cron Jobs";

		case "email":

			return "Email Addresses";

		case "dns_rec":

			return "DNS Records";

		case "host_cgi":

			return "CGI Hosting";

		case "host_php":

			return "PHP Hosting";

		case "host_ssl":

			return "SSL Hosting";

		case "shell_user":

			return "Shell Users";

		default:

			return "FIX ME";

			break;
	}
}

// A function to find out if a user can add another item of the given permission
// Returns false on failure, true on success
function user_can_add($uid, $perm) {
	global $CONF, $db;

	$lim = user_have_permission($uid, $perm);

	if (!$lim) return false;
	if ($lim < 0) return true;

	switch ($perm) {
		case "domain":
			$domains = $db->run("get_domains_by_user_id", Array(uid => $uid));

			if (count($domains) < $lim) return true;
			else return false;

			break;

		case "database":
			$dbs = $db->run("get_databases_by_user_id", Array(uid => $uid));

			if (count($dbs) < $lim) return true;
			else return false;

			break;

		case "email":
			$mails = $db->run("get_mail_users_by_user_id", Array(uid => $uid));

			if (count($mails) < $lim) return true;
			else return false;

			break;

		case "dns_rec":
			$dns = $db->run("get_dns_recs_by_user_id", Array(uid => $uid));

			if (count($dns) < $lim) return true;
			else return false;

			break;

		case "host_cgi":
			$domains = $db->run("get_domains_by_user_id", Array(uid => $uid));

			$count = 0;

			foreach ($domains as $domain)
				if ("true" == $domain[host_cgi]) $count++;

			if ($count < $lim) return true;
			else return false;

			break;

		case "host_php":
			$domains = $db->run("get_domains_by_user_id", Array(uid => $uid));

			$count = 0;

			foreach ($domains as $domain)
				if ("true" == $domain[host_php]) $count++;

			if ($count < $lim) return true;
			else return false;

			break;

		case "host_ssl":
			$domains = $db->run("get_domains_by_user_id", Array(uid => $uid));

			$count = 0;

			foreach ($domains as $domain)
				if ("true" == $domain[host_ssl]) $count++;

			if ($count < $lim) return true;
			else return false;

			break;

		case "shell_user":
			$susers = $db->run("get_sys_users_by_user_id", Array(uid => $uid));

			if (count($susers) < $lim) return true;
			else return false;

			break;

		default:

			return false;

			break;
	}
}

// A function to print " checked" if the user has that permission. We return it as a string,
// rather then printing it here, because when used in a print statement, functions are called
// before the print statement itself, and we don't want to print something like this:
// "checked <input type=checkbox name=host_php value=true>"

function perm_checked($uid, $perm) {
	if (user_have_permission($uid, $perm)) return " checked";
}

// A function to find out if a a user has a permission
// Returns zero on no, and the number of allowed on true

function user_have_permission($uid, $perm) {
	global $db;

	$row = $db->run("get_permission_by_user_id_and_perm", Array(uid => $uid, perm => $perm));

	if ($row[val] == "yes") return $row[lim];
	else return 0;
}

// A function to find out if a domain id belongs to a user id
// returns true if true, or if the user is an admin.

function user_have_domain($uid, $did) {
	global $db;

	if (is_admin()) return true;

	$domains = $db->run("get_domains_by_user_id", Array(uid => $uid));

	foreach ($domains as $domain) {
		if ($domain[id] == $did) return true;
	}

	return false;
}

// A function to make a page require the user be an admin.

function req_admin() {
	if (!is_admin()) {
		nav_top();

		print __('You are not authorized to view this page');

		nav_bottom();

	}
}

// A function to find out whether or not this user is an admin. Used as the primary
// security method, and is used just about everywhere

function is_admin() {
  global $status;

  return $status['is_admin'];
}

// A function that should be used at the top of every main page

function nav_top() {
  global $js_alerts, $page_title, $shell_output, $db, $logged_in, $status;

	if($status['db_panic']) {
		array_push($db->status_mesg, "The database connection is down. Make sure the mysql server is running, the DBI and DBD::mysql modules for perl are installed, and your admin password is the same as the mysql admin user's password.");
	}

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"><html><head>';
	// Print page title if there is one. Otherwise, print a generic title
	print '<title>';
	if ($page_title) print $page_title;
	else print "RavenCore";
	print '</title>
<meta http-equiv="Content-Type" content="text/html; charset=' . locale_getcharset() . '">
<meta name="keywords" content="ravencore, open source, control panel, hosting panel, hosting control panel, hosting software, free, webhosting software" />
<meta name="description" content="A Free and Open Source Hosting Control Panel for Linux" />
<meta name="authors" content="Cormander" />
<meta name="owner" content="RavenCore" />
<meta name="copywrite" content="Copyright 2005 Corey Henderson">
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
<style type="text/css" media="screen">@import "./css/style.css";</style>
<script type="text/javascript" src="js/help_menu.js">
</script>
<script src="js/ajax.js">
</script>
';
	// If the alert() function was called at all, output its contents here. We do it in the
	// <head> section of the page so that you see the error message before the page reloads
	if (sizeof($js_alerts) > 0) {
		print '<script type="text/javascript">';

		foreach($js_alerts as $alert) print 'alert("' . $alert . '");';

		print '</script>';
	}

	print '</head><body>';

	print '

<div id="container">

<div id="header"></div>

<table height=70% width=100%><tr>
<td valign=top>

<div id="topspan">
<span id="help_menu">&nbsp;</span>
</div>

<div id="content">';

	// only show the top menu if we're logged in
	if ($logged_in == 1) {

		print '<ul>';
		// Admins get to see a whole lot more then normal users
		if (is_admin())
		{

			if( ! $status['db_panic'] ) {

				print '<li class="menu"><a href="users.php" onmouseover="show_help(\'' . __('List control panel users') . '\');" onmouseout="help_rst();">' . __('Users') . ' (';

				$users = $db->run("get_users");

				print count($users);

				print ')</a></li>';

				if (have_domain_services()) {
					print '<li class="menu"><a href="domains.php" onmouseover="show_help(\'' . __('List domains') . '\');" onmouseout="help_rst();">' . __('Domains') . ' (';

					$domains = $db->run("get_domains");

					print count($domains);

					print ')</a></li>';
				}

				if (have_service("mail")) {
					print '<li class="menu"><a href="mail.php" onmouseover="show_help(\'' . __('List email addresses') . '\');" onmouseout="help_rst();">' . __('Mail') . ' (';

					$mails = $db->run("get_mail_users");

					print count($mails);

					print ')</a></li>';
				}

				if (have_database_services()) {
					print '<li class="menu"><a href="databases.php" onmouseover="show_help(\'' . __('List databases') . '\');" onmouseout="help_rst();">' . __('Databases') . ' (';

					$dbs = $db->run("get_databases");

					print count($dbs);

					print ')</a></li>';
				}

				if (have_service("dns")) {
					print '<li class="menu"><a href="dns.php" onmouseover="show_help(\'' . __('DNS for domains on this server') . '\');" onmouseout="help_rst();">' . __('DNS') . ' (';

					$count = 0;

					$domains = $db->run("get_domains");

					foreach ($domains as $domain) {
						if ($domain[soa]) $count++;
					}

					print $count;

					print ')</a></li>';
				}

				// log manager currently disabled, it broke somewhere along the line :)
				// if(have_service("web")) print '<li class="menu"><a href="log_manager.php" onmouseover="show_help(\'View all server log files\');" onmouseout="help_rst();">Logs</a></li>';
			}

			print '<li class="menu"><a href="system.php" onmouseover="show_help(\'' . __('Manage system settings') . '\');" onmouseout="help_rst();">' . __('System') . '</a></li>';

		} else {
			print '<li class="menu"><a href="users.php" onmouseover="show_help(\'' . __('Goto main server index page') . '\');" onmouseout="help_rst();">' . __('Main Menu') . '</a></li>
<li class="menu"><a href="domains.php" onmouseover="show_help(\'' . __('List your domains') . '\');" onmouseout="help_rst();">' . __('My Domains') . '</a></li>';

			if (have_service("mail")) print '<li class="menu"><a href="mail.php" onmouseover="show_help(\'' . __('List all your email accounts') . '\');" onmouseout="help_rst();">' . __('My email accounts') . '</a></li>';
		}

		print '<li class="menu right"><a href="logout.php" onmouseover="show_help(\'' . __('Logout') . '\');" onmouseout="help_rst();" onclick="return confirm(\'' . __('Are you sure you wish to logout?') . '\');">' . __('Logout') . '</a></li></ul>
<hr style="visibility: hidden;">';

	}

	print '<div><!--ERRORS--></div>';

}

// A function that should be used at the very bottom of every main page

function nav_bottom() {

  global $db;

	?>

</div>

</td></tr></table>

<div id="footer">

<a href="http://www.ravencore.com/">RavenCore Hosting Control Panel</a>
</div>


</div>

</body>
</html>
<?php

	$output = ob_get_contents();

	ob_end_clean();

	//
	if (is_array($_SESSION['status_mesg'])) {
		foreach ($_SESSION['status_mesg'] as $val) {
			array_push($db->status_mesg, $val);
		}

		unset ($_SESSION['status_mesg']);

	} else if ($_SESSION['status_mesg']) {
		array_push($db->status_mesg, $_SESSION['status_mesg']);
		unset ($_SESSION['status_mesg']);
	}

	if (is_array($db->status_mesg)) {
		foreach ($db->status_mesg as $val){
			$error .= $val . '<br/>';
		}
	}

	if ($error) {
		$output = str_replace('<!--ERRORS-->','<font size="2" color=red><b>' . $error . '</b></font>',$output);
	}

	print $output;

	rc_exit();

}

function selection_array($name, $selected, $num, $options, $arr = Array()) {
	global $db;

	$str = '<select name="' . $name . '' . ( $num ? '[' . ( $num == -1 ? '' : $num ) . ']' : '' ) . '" ' . $options . '>';

	foreach ($arr as $key => $val) {

		$str .= '<option value="' . $key . '"';

		// I SERIOUSLY want to strangle someone here ... string "NULL" is == to integer 0 ... wtf?
		// this is a dirty ugly hack...
		if (is_array($selected) ) {
			foreach ( $selected as $sey => $sal ) {
				if ( strlen($sal) == strlen($key) and $key == "NULL" ) {
					$str .= ' selected';
				} else if ( $key != 0 and $sal == $key ) {
					$str .= ' selected';
				}
			}
		} else {

			if ( strlen($selected) == strlen($key) and $key == "NULL" ) {
				$str .= ' selected';
			} else if ( $key != 0 and $selected == $key ) {
				$str .= ' selected';
			}
		}

		$str .= '>' . $val . '</option>' ;

	}

	$str .= '</select>';

	return $str;
}

function selection_users($uid = 0, $num = 0, $select_opt = "") {
	global $db;

	$str = '<select name=uid' . ( $num ? '[' . $num . ']' : '' ) . ' ' . $select_opt . '>';

	$users = $db->run("get_users");

	$str .= '<option value=0>' . __('No One') . '</option>';

	foreach ($users as $user) {
		$str .= '<option value="' . $user['id'] . '"';

		if ($user['id'] == $uid) $str .= ' selected';

		$str .= '>' . $user['name'] . '</option>';
	}

	$str .= '</select>';

	return $str;
}

function selection_domains($uid = 0, $did = 0, $num = 0, $select_opt = "") {
	global $db;

	$str = '<select name=did' . ( $num ? '[' . $num . ']' : '' ) . ' ' . $select_opt . '>';

	if ($uid) $domains = $db->run("get_domains_by_user_id", Array(uid => $uid));
	else $domains = $db->run("get_domains");

	$str .= '<option value=0>' . __('Select a domain') . '</option>';

	foreach ($domains as $domain) {
		$str .= '<option value="' . $domain[id] . '"';

		if ($domain[id] == $did) $str .= ' selected';

		$str .= '>' . $domain[name] . '</option>';
	}

	$str .= '</select>';

	return $str;
}

?>
