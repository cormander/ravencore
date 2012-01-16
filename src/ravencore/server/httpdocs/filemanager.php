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

if (!$did) send_to_url("users.php");

$suser = $db->run("get_sys_user_by_domain_id", Array(did => $did));

$_SESSION[user] = $suser[login];
$_SESSION[password] = $suser[passwd];

$_SESSION[server] = "127.0.0.1";
$_SESSION[language] = $locales[$current_locale][filemanager];
$_SESSION[port] = 21;

send_to_url("filemanager/");

?>
