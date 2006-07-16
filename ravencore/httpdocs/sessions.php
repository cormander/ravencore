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

if ($action == "delete")
{
    $sql = "delete from sessions where id = '$_POST[session]' and session_id != '$session_id'";
    $db->data_query($sql);

    goto($_SERVER[PHP_SELF]);
} 

nav_top();

$sql = "select * from sessions";
$result = $db->data_query($sql);

print '<form method=post><table width=600><tr><th width=20%>' . __('Login') . '</th><th width=20%>' . __('IP Address') . '</th><th width=20%>' . __('Session Time') . '</th><th width=20%>' . __('Idle Time') . '</th><th width=20%>' . __('Delete') . '</th></tr>';

while ($row = $db->data_fetch_array($result))
{
    $sql = "select ( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) ) - ( ( to_days(created) * 24 * 60 * 60 ) + time_to_sec(created) ) as total, ( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) ) - ( ( to_days(idle) * 24 * 60 * 60 ) + time_to_sec(idle) ) as idle from sessions where id = '$row[id]'";
    $result_session = $db->data_query($sql);

    $row_session = $db->data_fetch_array($result_session);

    print '<tr><td>';

    if ($row[session_id] == $session_id) print '<b>' . $row[login] . '</b>';
    else print $row[login]; 
    // if the session is longer then an hour, make the date_disp contain the H
    if ($row_session[total] > 3600) $date_disp = "H:i:s";
    else $date_disp = "i:s";

    print '</td><td>' . $row[location] . '</td><td>' . date($date_disp, mktime(0, 0, $row_session[total], 1, 1, 1)) . '</td><td>' . date('i:s', mktime(0, 0, $row_session[idle], 1, 1, 1)) . '</td><td><input type=radio name=session value="' . $row[id] . '"';

    if ($row[session_id] == $session_id) print ' disabled';

    print '></td></tr>';
} 

print '<tr><td colspan=4></td><td><input type=hidden name=action value=delete><input type=submit value="' . __('Remove') . '"></td></tr></table></form>';

nav_bottom();

?>