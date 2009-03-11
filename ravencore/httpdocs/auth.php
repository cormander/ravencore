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

// Define our regular expressions. They don't contain ^ or $ in them, because they are used with
// each other in some places. Be sure you supply ^ before the string, and $ after it:
// eg: preg_match('/^'.REGEX.'$/')
// preg_match('/^'.REGEX.'@'.REGEX2.'$/')
// to get the full effectiveness of the regular expressions.

define("REGEX_MAIL_NAME", '([a-zA-Z\d]+((\.||\-||_)[a-zA-Z\d]?)?)*[a-zA-Z\d]');
define("REGEX_DOMAIN_NAME", '([a-zA-Z\d]+((\.||\-)[a-zA-Z\d]?)?)*[a-zA-Z\d]\.[a-zA-Z]+');
define("REGEX_PASSWORD", '[a-zA-Z\d]*');

// Initialize some variables
$js_alerts = array();

// A list of variables that are allowed to use the GET and POST methods
$reg_glob_vars = array('uid', 'did', 'mid', 'action', 'debug', 'dbid', 'dbu', 'page_type');

foreach ($reg_glob_vars as $val)
{
    if (isset($_REQUEST[$val]))
    {
        $eval_code = '$' . $val . ' = $_REQUEST[\'' . $val . '\'];';

        eval($eval_code);
    }
}

// Include our function file
include "functions.php";

session_start(); // session_read() is called here automatically

ob_start();

// $db = new rcclient; // commented out because it's called in the rcclient.php file which is set to auto_prepend
// $db is a common variable name... for compat with other apps, the rcclient is actually in $rcdb, but since
// I don't want to bother to change all the current PHP code from $db -> $rcdb (yes, I'm lazy), just make $db
// a reference to it..
// I banged my head against my monitor for hours trying to get phpmyadmin to work with the ravencore custom
// sessions via the socket.... this was one of the things that had to be figured out and changed.
$db = &$rcdb;

// after session_start, we set our language

if($_REQUEST['lang']) $_SESSION['lang'] = $_REQUEST['lang'];

// if no session variable for language, set it to the default locale
if (!$_SESSION['lang'])
{
  $_SESSION['lang'] = $db->do_raw_query('get_default_locale');
}
// if we still don't have it (should never happen), set it to english
if (!$_SESSION['lang'])
{
  $_SESSION['lang'] = 'en_US';
}
// set the locale
locale_set($_SESSION['lang']);

// quick hack to tell the nav_top if we're logged in or not
$logged_in = 0;

// check if we're authenticated.
if($db->auth_resp != 1)
{

  $login_error = $db->auth_resp;

  include "login.php";

}
			  
// NOTE! Anything beyond this point is considered a logged in user
  
// read logged_in comment above. beyond this point we assume we are logged in
$logged_in = 1;

// if we're posting the gpl_agree, tell the server so
if($action == "gpl_agree" && $_REQUEST['gpl_agree'] == "yes")
{
  $db->do_raw_query('gpl_agree');
  goto($_SERVER['PHP_SELF']);
}

// if we're posting the update_conf, update conf with the incoming array
if($action == "update_conf" and is_array($_REQUEST['CONF_UPDATE']))
{
  foreach( $_REQUEST['CONF_UPDATE'] as $key => $val )
    {
      if( $key and isset($val) )
	{
	  $db->do_raw_query('set_conf_var ' . $key . ' ' . $val);
	}

    }
# tell rcserver to reload, and wait 2 seconds for it to do so
  $db->do_raw_query('reload I called set_conf_var at least once');
  sleep(2);

  goto($_SERVER['PHP_SELF']);
}

// ask for the current state of things. this needs to be done AFTER all post actions for this file
// so that we have the latest and greatest info
$status = $db->do_raw_query("session_status");

$CONF = &$status['CONF'];

// check to see if the GPL was accepted. if not, send us to that page
if( ! $status['gpl_check'] and $_SERVER['PHP_SELF'] != '/logout.php' )
{

  nav_top();

  if ($action == "gpl_agree")
    {
      print '<b><font color="red">' . __('You must agree to the GPL License to use RavenCore.') . '</font></b><p>';
    }

  print __('Please read the GPL License and select the "I agree" checkbox below') . '<hr><pre>';

  $h = fopen("../LICENSE", "r");

  fpassthru($h);

  print __('The GPL License should appear in the frame below') . ': </pre>';

?>
<iframe src="GPL" width="675" height="250">
</iframe>
<p>
<form method="post">
   <input type="checkbox" name="gpl_agree" value="yes"> <?php echo __('I agree to these terms and conditions.') ?>

<p>

<input type="submit" value="Submit"> <input type="hidden" name="action" value="gpl_agree">
</form>
<?php

   nav_bottom();

}

// check to see if configuration is complete. if not, send us to the configuration page
if( ! $status['config_complete'] and $_SERVER['PHP_SELF'] != '/logout.php' )
{

  nav_top();

  print '<div align=center>' . __('Welcome, and thank you for using RavenCore!') . '</div>
         <p>' . 
    __('You installed and/or upgraded some packages that require new configuration settings.') . ' ' .
    __('Please take a moment to review these settings. We recomend that you keep the default values, ') .
    __('but if you know what you are doing, you may adjust them to your liking.')
    . '
      <div align=center>
      <form method=post>
      <input type=hidden name=action value="update_conf">
      <table>';

  foreach( $status['UNINIT_CONF'] as $key => $val )
    {
      
      print '<tr><td>' . $key . ':</td>' .
	'<td><input type="text" name="CONF_UPDATE[' . $key . ']" value="' . $val . '"></td></tr>';

    }

  print '<tr><td colspan=2 align=right><input type="submit" value="' . __('Submit') . '"></td></tr></table></div>';
  
  nav_bottom();
  
}

// send the admin user to the system page if the database is in panic mode
if($_SERVER['PHP_SELF'] != '/system.php' and $status['db_panic'] and $action == 'login')
{
  goto("/system.php");
}

// if the config is complete, GPL accepted, and install_complete is zero, complete the install
if( $status['config_complete'] and $status['gpl_check'] and ! $status['install_complete'] )
{
  $db->do_raw_query('complete_install');
}

// logging in - redirect to pick up any session variables
if($action == 'login')
{
  goto($_SERVER['PHP_SELF']);
}

if( ! $status['db_panic'] )
{
  // create user object if this is not an admin user  
  if( ! is_admin() )
    {
      $uid = $status['user_data']['id'];
    }
  
  // sanity check.. if we're not an admin and have no user id, there's a problem
  if( ! is_admin() and ! $uid )
    {

      nav_top();

      print "Unable to load page, no uid from session";

      nav_bottom();

    }
  
  if ( $uid )
    {
      $u = new user($uid);
    }

  if ( $did )
    {
      $d = new domain($did);
    }
  
  // If we have a $did and no $uid, we're an admin looking at a user's domain page. Get the $uid
  if (!$uid and $did)
    {
      $u = new user($d->info['uid']);
      
      $uid = $u->uid;
    }
  
  // If we have a $did, match it with the given $uid. If we fail, goto the user's main page
  if ( $did and $u and ! $u->owns_domain($did) and !is_admin())
    {
      goto("users.php");
    }
  
} // end if( ! $status['db_panic'] )

?>
