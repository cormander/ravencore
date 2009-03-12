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

if ($action == "delete") {
  $db->do_raw_query('session_remove ' . $_POST['session']);
  goto($_SERVER['PHP_SELF']);
}

nav_top();

$sessions = $db->do_raw_query('session_list');

//print date('Y-m-d H:i:s','1169622595');

//print '<pre>';print_r($sessions);exit;

print '<form method=post>
<table class="listpad" width=600><tr>
<th class="listpad" width=20%>' . __('Login') . '</th>
<th class="listpad" width=20%>' . __('IP Address') . '</th>
<th class="listpad" width=20%>' . __('Session Time') . '</th>
<th class="listpad" width=20%>' . __('Idle Time') . '</th>
<th class="listpad" width=20%>' . __('Delete') . '</th></tr>';

foreach ($sessions as $session_id => $row ) {
	print '<tr><td class="listpad">';

	if( $session_id == session_id() ) print '<b>' . $row['user'] . '</b>';
	else print $row['user'];

	$time = date('i:s', mktime(0, 0, time() - $row['created'], 1, 1, 1));
	$idle = date('i:s', mktime(0, 0, time() - $row['accessed'], 1, 1, 1));

	// 'accessed' is updated after the page loads (session_write), so for this user's session, say
	// and idle time of zero
	if( $session_id == session_id() ) $idle = '00:00';

	print '</td><td class="listpad">' . $row['ip'] . '</td>
<td class="listpad">' . $time . '</td>
<td class="listpad">' . $idle . '</td>
<td class="listpad"><input type=radio name=session value="' . $session_id . '"';

	if ($session_id == session_id()) print ' disabled';

	print '></td></tr>';
}

print '<tr><td class="listpad" colspan=4></td><td class="listpad"><input type=hidden name=action value=delete><input type=submit value="' . __('Remove') . '"></td></tr></table></form>';

nav_bottom();

?>
