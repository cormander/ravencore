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

// set our some of our php_admin_values that we can't define in the ravencore.httpd.conf
error_reporting(E_ALL ^ E_NOTICE);

// Initialize some variables
$js_alerts = array();
$CONF = array();
$conf_not_complete = false;

// Include our function file
include "functions.php";

// A list of variables that are allowed to use the GET and POST methods
$reg_glob_vars = array('uid', 'did', 'mid', 'action', 'dbid', 'dbu', 'page_type');

foreach ($reg_glob_vars as $val)
{
    if (isset($_REQUEST[$val]))
    {
        $eval_code = '$' . $val . ' = $_REQUEST[\'' . $val . '\'];';

        eval($eval_code);
    }
}

// start our session
$session = new session();

// set our server object
$server = new server();

// Connect to the database
require_once './adodb/adodb.inc.php';
$db =& ADONewConnection('mysql');

$db->SetFetchMode(ADODB_FETCH_ASSOC);

$dbConnect = $db->Connect($CONF['MYSQL_ADMIN_HOST'], $CONF['MYSQL_ADMIN_USER'],
			  $CONF['MYSQL_ADMIN_PASS'], $CONF['MYSQL_ADMIN_DB']);


if (!$dbConnect)
{
  /*
    nav_top();

    print __('Unable to get a database connection.');

    nav_bottom();

  exit;
  */

  $server->db_panic();

}


// read our database configuration settings
$server->read_conf();

if (!$_SESSION['lang'] and $CONF['DEFAULT_LOCALE'])
{
  locale_set($CONF['DEFAULT_LOCALE']);
}

// set our locale if not already
if (!$_SESSION['lang'] and !$CONF['DEFAULT_LOCALE'])
{
  $_SESSION['lang'] = @ereg_replace('\..*', '', shell_exec('echo $LANG'));
}

// If we're trying to login, run the authentication
if ($action == "login")
{

  if( ! $session->do_auth($_POST['user'], $_POST['pass']) )
    {

      if( ! $server->db_panic )
	{      
	  $sql = "insert into login_failure set date = now(), login = '" . $_POST['user'] . "'";
	  $db->Execute($sql);
	}

      $login_error = $session->login_error;
      
      syslog(LOG_WARNING, "Login failure for user " . $_POST['user'] . "from " . $_SERVER['REMOTE_ADDR']);
      
      include("login.php");
      
      exit;
      
    }

  if ( ! $server->check_version() )
    {
      
      // real
      if ($_POST['user'] == $CONF['MYSQL_ADMIN_USER'])
	{
	  $_SESSION['status_mesg'] = __('Control panel is locked for users, because your "lock if outdated" setting is active, and we appear to be outdated.');
	}
      else
	{
	  $login_error = __('Login locked because control panel is outdated.');
	  
	  syslog(LOG_WARNING, "Control panel outdated");
	  
	  include("login.php");
	  
	  exit;
	}
    }

    // slave server socket_cmd
    if ($_POST['socket_cmd'])
    {
        if ($CONF['SERVER_TYPE'] == "slave")
        {
            socket_cmd(trim(urldecode($_POST['socket_cmd'])));

            syslog(LOG_INFO, "Posted command '$_POST[socket_cmd]' from $_SERVER[REMOTE_ADDR]");

            exit;
        }
        else
        {
            nav_top();

            print __('API command failed. This server is configured as a master server.');

            nav_bottom();

            syslog(LOG_INFO, "API command attempted on master server from $_SERVER[REMOTE_ADDR]");

            exit;
        }
    }

    // send the admin user to the system page if the server is in panic mode
    if($server->db_panic)
      {
	goto("system.php");
      }

    // send ourself back to where we were upon login
    $url = $_SERVER['PHP_SELF'];
    // only add the query string if it exists
    if ($_SERVER['QUERY_STRING'])
    {
        $url .= '?' . $_SERVER['QUERY_STRING'];
    }

    // only do a header redirect if we have no pending alerts
    if (!$js_alerts)
    {
        goto($url);
    }
} // End auth login if

// We can't proceed past this point if we are a slave server
if ($CONF['SERVER_TYPE'] == 'slave')
{
    exit;
}

if( ! $session->found() )
{
  // No session found.
  include "login.php";

  exit;

}

if( ! $server->db_panic )
{
  
  // create user object if this is not an admin user
  
  if( ! is_admin() )
    {
      
      $uid = $session->get_user_id();

      $u = new user($uid);
      
    }
  
  // make sure the installation of the server is complete before we continue
  
  $server->install_checks();
  
  // NOTE! Anything beyond this point is considered a logged in user
  
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
  
  // TODO:
  // Fix this. if we switch a domains' user, this stops it cold
  //
  
  if ( $did and $u and ! $u->owns_domain($did) )
    {
    goto("users.php?uid=$uid");
    }
  
}

?>
