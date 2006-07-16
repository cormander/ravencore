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

if (!$did) goto("users.php?uid=$uid");

if (!user_can_add($uid, "database") and !is_admin()) goto("users.php?user=$uid");

if ($action == "add")
{
    $sql = "create database $_POST[name]";

    $db->data_query($sql);

    $sql = "insert into data_bases set name = '$_POST[name]', did = '$did'";
    $db->data_query($sql);
    
    goto("databases.php?did=$did");

} 

nav_top();

$sql = "select * from domains where id = '$did'";
$result = $db->data_query($sql);

$row = $db->data_fetch_array($result);

print '' . $lang['add_db_adding_a_database_for'] . ' ' . $row['name'] . '<p>

<form method="post">

' . __('Name') . '": <input type="text" name=name>

<p>

<input type="submit" value="' . __('Add Database') . '">
<input type="hidden" name=action value="add">
<input type="hidden" name=did value="' . $did . '">

</form>';

nav_bottom();

?>