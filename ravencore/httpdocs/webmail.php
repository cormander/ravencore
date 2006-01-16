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

if (!$did and !$mid) goto("users.php");

$sql = "select concat(mail_name,'@',name) as login_username, passwd from mail_users m, domains d where d.id = m.did and m.did = '$did' and m.id = '$mid'";
$result =& $db->Execute($sql);

$row =& $result->FetchRow();

$_SESSION['login_username'] = $row['login_username'];
$_SESSION['secretkey'] = $row['passwd'];

goto("webmail/src/redirect.php");

?>