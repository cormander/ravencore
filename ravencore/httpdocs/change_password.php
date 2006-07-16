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
// if we're being included, don't call the auth.php file as we have alredy done so
if ($being_included != true) include "auth.php";

req_admin();

if ($action == "change")
{
  if (!valid_passwd($_POST['new_pass']))
    {
      alert(__("The new password must be greater than 4 characters and not a dictionary word"));
    }
  else
    {
      if( $server->db_panic )
	{
	  $_SESSION['password'] = $_POST['new_pass'];
	}
      
      if ( $db->change_passwd($_POST['old_pass'], $_POST['new_pass']) )
	{
	  $_SESSION['status_mesg'] = 'Password change successful!';
	}
      else
	{
	  $_SESSION['status_mesg'] = 'Old password incorrect. Password not changed.';
	}

      // if we are being included, send is to ourself. Otherwise, send us to the system page
      if ($being_included == true)
	{
	  unset($_SESSION['status_mesg']);
	  goto($_SERVER['PHP_SELF']);
	}
        else
	  {
	    goto("system.php");
	  }
    }
}

nav_top();

?>

<script type="text/javascript">

function validate_pw(f) {

  if(f.new_pass.value != f.confirm.value) {

    alert('<?php e_('Your passwords are not the same!') ?>');

    return false;

  } else return true;

}

</script>

<form method="post" onsubmit="return validate_pw(this)">
<table>
<tr><th colspan="2"><?php
// if our password is "ravencore", tell the user to change it
print ( $db->data_auth("ravencore") ? __('Please change the password for') . ' ' . $CONF['MYSQL_ADMIN_USER'] : __('Changing ') . $CONF['MYSQL_ADMIN_USER'] . __(' password!'));

?></th></tr>
<td><?php e_('Old Password')?>:</td>
<td><input type="password" name=old_pass></td>
</tr><tr>
<td><?php e_('New Password')?>:</td>
<td><input type="password" name=new_pass></td>
</tr><tr>
<td><?php e_('Confirm New')?>:</td>
<td><input type="password" name=confirm></td>
</tr><tr>
<td colspan="2" align="right"><input type="submit" value="<?php e_('Change Password')?>"> <input type="hidden" name=action value=change></td>
</tr>
</table>
</form>

<?php

nav_bottom();

?>
