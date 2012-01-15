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

req_admin();

if ($action == "update") {
	$ip = $_POST['ip'];
	$uid = ( $_POST['uids'] ? $_POST['uids'][$ip] : 0 );
	$did = ( $_POST['dids'] ? $_POST['dids'][$ip] : 0 );

	//  print '<pre>'; print_r($_POST); exit;
	$db->run('ip_update',
		Array(
			'ip' => $ip,
			'uid' => $uid,
			'did' => $did,
		)
	);

	openfile($_SERVER['PHP_SELF']);
}

if ($action == "delete") {
	$ip = $_REQUEST['ip'];
	$db->run('ip_delete', Array('ip' => $ip));
	openfile($_SERVER['PHP_SELF']);
}

nav_top();

$ips = $db->run('ip_list');

if (!is_array($ips)) {

	print $ips;
	nav_bottom();

}

print '<form name=main method=post>
<input type=hidden name=action value=update>
<input type=hidden name=ip>
<table class="listpad" width=600><tr>
<th class="listpad" width=20%>' . __('IP Address') . '</th>
<th class="listpad" width=20%>' . __('User') . '</th>
<th class="listpad" width=20%>' . __('Default Domain') . '</th>
<th class="listpad" width=20%>' . __('Active') . '</th></tr>';

$alldomains = Array( 0 => __('Select a Domain') );

$domains = $db->run("get_domains");

foreach ($domains as $domain) {
	$alldomains[$domain[id]] = $domain[name];
}

$userlist = Array( "NULL" => 'No One', 0 => __('Everyone') );

$domainlist['NULL'] = $alldomains;
$domainlist[0] = $alldomains;

$users = $db->run("get_users");

foreach ($users as $user) {
	$userlist[$user[id]] = $user['name'];

	$domainlist[$user[id]] = Array( 0 => __('Select a Domain') );

	foreach ($domains as $domain) {
		if ($domain[uid] != $user[id]) continue;
		$domainlist[$user[id]][$domain[id]] = $domain[name];
	}

}

//print '<pre>'; print_r($userlist);exit;

foreach ($ips as $ip => $row ) {
	//print '<pre>'; print_r($row);print '</pre>';

	print '<tr' . ( $row['active'] == "true" ? '' : ' class="redwarning"' ) . '>' .
		'<td class="listpad">' . $ip . '</td>' .
		'<td class="listpad">' . selection_array("uids", $row['uid'], $ip, "onchange=document.main.ip.value='" . $ip . "';document.main.submit()", $userlist) . '</td>' .
		'<td class="listpad">' . selection_array("dids", $row['default_did'], $ip, "onchange=document.main.ip.value='" . $ip . "';document.main.submit()", $domainlist[$row['uid']]) . '</td>' .
		'<td class="listpad">' . $row['active'] . ( $row['active'] == 'true' ? '' : ', <a style="color: yellow" href="ip_addresses.php?action=delete&ip=' . $ip. '">remove</a>' ) . '</td></tr>';
}

print '</table></form>';


nav_bottom();

?>
