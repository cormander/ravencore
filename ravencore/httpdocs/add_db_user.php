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

if (!$db or !$did) goto("users.php?user=$uid");

if ($action == "add")
{ 
    // does user already exist?
    $sql = "select count(*) as count from data_base_users where login = '$_POST[login]'";
    $result =& $db->Execute($sql);

    $row =& $result->FetchRow(); 
    // we can't use the admin username
    if ($_POST['login'] == $CONF['MYSQL_ADMIN_USER']) $row['count'] = 1;

    if ($row['count'] != 0) $error = "User $_POST[login] already exists";
    else
    {
        if (preg_match('/^' . REGEX_PASSWORD . '$/', $_POST[passwd]) and valid_passwd($_POST[passwd]))
        {
            $sql = "select * from data_bases where id = '$db'";
            $result =& $db->Execute($sql);

            $row =& $result->FetchRow();

            $sql = "grant select,insert,update,delete,create,drop,alter on $row[name].* to $_POST[login]@localhost identified by '$_POST[passwd]'";

            if ($db->Execute($sql))
            {
                $sql = "insert into data_base_users set login = '$_POST[login]', db_id = '$db', passwd = '$_POST[passwd]'";
                $db->Execute($sql);

                goto("databases.php?did=$did&db=$db");
            } 
            else alert($db->ErrorMsg());
        } 
        else
        {
            alert(__("Invalid password. Must only contain letters and numbers, must be atleast 5 characters, and not a dictionary word"));
            $_POST[passwd] = "";
        } 
    } 
} 

nav_top();

$sql = "select * from data_bases where id = '$db'";
$result =& $db->Execute($sql);

$row =& $result->FetchRow();

print __('Adding a user for database') . ' ' . $row['name'] . '<p>

<form method="post">

' . __('Login') . ': <input type="text" name=login>
<p>
' . __('Password') . ': <input type="password" name=passwd>
<p>

<input type="submit" value="' . __('Add User') . '">
<input type="hidden" name=action value="add">
<input type="hidden" name=did value="' . $did . '">
<input type="hidden" name=db value="' . $db . '">

</form>';

nav_bottom();

?>