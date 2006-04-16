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

$lang = $_SESSION['lang'];

// change session names so our variables carry over to phpmyadmin
// we call it twice, to override any previous phpmyadmin session we were in

for( $i = 0; $i < 2; $i++ )
{
  session_destroy();
  session_name('phpMyAdmin');
  session_start();
}

$_SESSION['login'] = $CONF[MYSQL_ADMIN_USER];
$_SESSION['passwd'] = $CONF[MYSQL_ADMIN_PASS];
$_SESSION['name'] = '';
$_SESSION['phpmyadmin_lang'] = $locales[$lang]['phpmyadmin'];

goto("phpmyadmin/");

?>