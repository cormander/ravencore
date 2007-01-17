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

if( $action == "phpmyadmin_login" )
{

  if( ! $db->do_raw_query('verify_passwd ' . $_POST['phpmyadmin_passwd']) )
    {
      $error = "Password incorrect";
    }
  else
    {
      
      $lang = $_SESSION['lang'];
      
      $_SESSION = array();

      $_SESSION['ravencore_login'] = 'admin'; // TODO: FIX THIS, its just a quick hack to get it working
      $_SESSION['ravencore_passwd'] = $_POST['phpmyadmin_passwd'];
      $_SESSION['ravencore_name'] = '';
      $_SESSION['ravencore_phpmyadmin_lang'] = $locales[$lang]['phpmyadmin'];
      
      goto("phpmyadmin/");
      
    }

}

?>
<center>
<h1>phpMyAdmin</h1>
<?php
if($error) print '<b><font color="red">' . $error . '</font></b><br/>';
?>
<b>Please re-authenticate your <?=$CONF['MYSQL_ADMIN_USER']?> password:</b>
<br />
<form method="post">
<input type="password" name="phpmyadmin_passwd"> <input type="submit" value="Submit">
<input type="hidden" name="action" value="phpmyadmin_login">
</form>
</center>
