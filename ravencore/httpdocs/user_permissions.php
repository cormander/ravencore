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

$perms = array();

if(have_domain_services()) $perms[] = 'domain';
if(have_database_services()) $perms[] = 'database';

if(have_service("mail")) $perms[] = 'email';

if(have_service("dns")) $perms[] = 'dns_rec';

if(have_service("web")) {
  
  //$perms[] = 'crontab';
  $perms[] = 'host_cgi';
  $perms[] = 'host_php';
  $perms[] = 'host_ssl';
  $perms[] = 'shell_user';

}

if($action == "update" and is_admin()) {
  
  $sql = "delete from user_permissions where uid = '$uid'";
  mysql_query($sql);
  
  foreach($perms as $perm) {
    
    $val = $_POST[$perm];
    $tmp = $perm . "_max";
    $lim = $_POST[$tmp];
    
    $sql = "insert into user_permissions set uid = '$uid', perm = '$perm', val = '$val', lim = '$lim'";
    mysql_query($sql);

  }  
  
  goto("users.php?uid=$uid");
  
}

if(!$uid) goto("users.php");

nav_top();

if(is_admin()) {

  print '<form method="post" name=main>';

  if($perms) {
    
    print '<table width=400>
<tr><th>' . __('This user can') . ':</th></tr>
<tr><td>';
    
    $i = 0;
    
    foreach($perms as $perm) {
      
      $have_perm = user_have_permission($uid, $perm);
      
      if($have_perm == 0) $have_perm = "";
      
      print '<input type="checkbox" name=' . $perm . ' value="yes" onclick="if(document.main.' . $perm . '.checked) document.main.' . $perm . '_max.select();"' . perm_checked($uid, $perm) . '> ' . __('Create') . ' ' . perm_into_word($perm) . '
<br>
&nbsp;&nbsp;&nbsp;Limit <input type="text" name=' . $perm . '_max size=1 value="' . $have_perm . '"><p>';
      
    }

    print '</td></tr></table>';

  }

?>

<p>
<?php e_('Note: A negative limit mean unlimited')?>
<p>
<input type="submit" value="<?php e_('Update')?>">
<input type="hidden" name=uid value="<?php print $uid; ?>">
<input type="hidden" name=action value=update>
</form>

<?php

} else {

  foreach($perms as $perm) {

    $lim = user_have_permission($uid, $perm);

    switch($perm) {

    case "domain":

      if($lim) print __("You can add up to $lim domains");
      else print __("You can't add domains");
      break;

    case "database":

      if($lim) print __("You can add up to $lim databases");
      else print __("You can't add databases");
      break;

    case "crontab":
      // NEED TO RE-DO CRONTAB MANAGEMENT

      if($lim) print __("You can add up to $lim cron jobs");
      else print __("You can't add cron jobs");
      break;

    case "email":

      if($lim) print __("You can add up to $lim email addresses");
      else print __("You can't add email addresses");
      break;

    case "dns_rec":

      if($lim) print __("You can add up to $lim DNS records");
      else print __("You can't add DNS records");
      break;

    case "host_cgi":

      if($lim) print __("You can add cgi to hosting on up to $lim domains");
      else print __("You can't add cgi to hosting on any domains");
      break;

    case "host_php":

      if($lim) print __("You can add php to hosting on up to $lim domains");
      else print __("You can't add php to hosting on any domains");
      break;

    case "host_ssl":

      if($lim) print __("You can add ssl to hosting on up to $lim domains");
      else print __("You can't add ssl to hosting on any domains");
      break;

    case "shell_user":
      
      if($lim) print __("You can have up to $lim shell users");
      else print __("You can't add shell users");
      break;

    default:

      break;

    }

    print '<p>';

  }

}


nav_bottom();

?>