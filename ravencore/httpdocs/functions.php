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

// scan the classes dir and include all of the files
 
$dir = dir('classes');

while (false !== ($entry = $dir->read())) {  
  // if this element is a directory, and not an implied "." or ".."
  if( ! ereg('^\.',$entry) )
    {
      include 'classes/' . $entry;
    }
}

// get our locale functions
include "functions/locale.php";

//
function update_parameter($type_id, $param, $value)
{

    global $db;

    $sql = "delete form parameters where type_id = '$type_id' and param = '$param'";
    $db->Execute($sql);

    $sql = "insert into parameters set type_id = '$type_id', param = '$param', value = '$value'";
    $db->Execute($sql);
}
// a basic password validation function
function valid_passwd($passwd)
{
    if (function_exists("pspell_new"))
    {
        // we use the english dictionary
        $d = pspell_new("en");
        // if the string is a word, it isn't a safe password
        if (pspell_check($d, $passwd)) return false;
    }
    // if the string is less than 5 characters long, it isn't a safe password
    if (strlen($passwd) < 5) return false;

    return true;
}
// A function to tell us if we have any "domain" services, or in other words,
// any service that requires the use of the domains table to function
function have_domain_services()
{
    // domain services as of version 0.0.1
    if (have_service("web") or have_service("mail") or have_service("dns")) return true;

    return false;
}
// A function to tell us whether or not we have database services.
// Right now it only looks for mysql, but this will in the future look for other db types
// such as pgsql, mssql, etc
function have_database_services()
{
    if (have_service("mysql")) return true;
    else return false;
}

function have_service($service)
{
    global $CONF, $server;

    // since there are so many damn places that use this function I just made it an
    // alias for the new one :P
    return $server->module_enabled($service);

    //if (is_executable("$CONF[RC_ROOT]/conf.d/$service.conf")) return true;
    //else return false;

}
// A function that requires the existances of the webserver to load the page
// Must use before you output any headers.
function req_service($service)
{
    if (!have_service($service))
    {
        nav_top();

        print __('This server does not have ' . $service . ' installed. Page cannot be displayed.');

        nav_bottom();

        exit;
    }
}

// A function to convert the number of bytes into K or MB
function readable_size($size)
{
    if ($size > 1048576)
    {
        $size /= 1048576;
        $size = round($size, 2) . 'MB';
    }
    else if ($size = round(($size / 1024), 2)) $size .= 'K';

    return $size;
}
// A function to delete a domain's log file
function delete_log($did, $log_file)
{
  $d = new domain($did);

    $domain_name = $d->name();

    socket_cmd("log_del $domain_name $log_file");
}


// A function to tell us whether or not given string is an ip address. I got the core
// routines off of php.net, but made some of my own changes to make it return a bool value
function is_ip($ip)
{
    $ip = trim($ip);
    if (strlen($ip) < 7) return false;
    if (!ereg("\.", $ip)) return false;
    if (!ereg("[0-9.]{" . strlen($ip) . "}", $ip)) return false;
    $ip_arr = split("\.", $ip);
    if (count($ip_arr) != 4) return false;
    for ($i = 0;$i < count($ip_arr);$i++)
    {
        if ((!is_numeric($ip_arr[$i])) || (($ip_arr[$i] < 0) || ($ip_arr[$i] > 255))) return false;
    }
    return true;
}
// A function to queue a message to be output with a javascript alert
// It must be called before the nav_top function
function alert($message)
{
    global $js_alerts;

    array_push($js_alerts, $message);
}
// A function to do a header to the given location. Must be called before output
// goes to the browser. This function is used just about everywhere.
function goto($url)
{

  global $session;

  // session variables may not be saved before the browser changes to the new page, so we need to
  // save them here
  
  $session->end();
  
  header("Location: $url");
  
  exit;

}
// A function to try to restart the mysql server if we failed to get a connection
function mysql_panic()
{
    print __("Unable to connect to DB server! Attempting to restart mysql") . " <br><b>";

    socket_cmd("mysql_restart");
    // while we have a restart lockfile, hang
    do
    {
        print ".<br>";

        sleep(1);
    }
    while (file_exists("/tmp/mysql_restart.lock"));

    print "</b>" . __("Restart command completed. Please refresh the page.") . "<p>" . __("If the problem persists, contact the system administrator");

    exit;
}
// Returns the correct word assositated with a permission
function perm_into_word($perm)
{
    switch ($perm)
    {
        case "domain":

            return "Domains";

        case "database":

            return "Databases";

        case "crontab":

            return "Cron Jobs";

        case "email":

            return "Email Addresses";

        case "dns_rec":

            return "DNS Records";

        case "host_cgi":

            return "CGI Hosting";

        case "host_php":

            return "PHP Hosting";

        case "host_ssl":

            return "SSL Hosting";

        case "shell_user":

            return "Shell Users";

        default:

            return "FIX ME";

            break;
    }
}
// A function to find out if a user can add another item of the given permission
// Returns false on failure, true on success
function user_can_add($uid, $perm)
{
    global $CONF, $db;

    $lim = user_have_permission($uid, $perm);

    if (!$lim) return false;
    if ($lim < 0) return true;

    switch ($perm)
    {
        case "domain":
            $sql = "select count(*) as count from domains where uid = '$uid'";
            $result =& $db->Execute($sql);

            $row =& $result->FetchRow();

            if ($row[count] < $lim) return true;
            else return false;

            break;

        case "database":
            $sql = "select count(*) as count from data_bases b, domains d where did = d.id and uid = '$uid'";
            $result =& $db->Execute($sql);

            $row =& $result->FetchRow();

            if ($row[count] < $lim) return true;
            else return false;

            break;

        case "crontab":
            // NEED TO RE-DO CRONTAB MANAGEMENT
            break;

        case "email":
            $sql = "select count(*) as count from mail_users m, domains d where did = d.id and uid = '$uid'";
            $result =& $db->Execute($sql);

            $row =& $result->FetchRow();

            if ($row[count] < $lim) return true;
            else return false;

            break;

        case "dns_rec":
            $sql = "select count(*) as count from dns_rec r, domains d where did = d.id and uid = '$uid'";
            $result =& $db->Execute($sql);

            $row =& $result->FetchRow();

            if ($row[count] < $lim) return true;
            else return false;

            break;

        case "host_cgi":
            $sql = "select count(*) as count from domains where host_cgi = 'true' and uid = '$uid'";
            $result =& $db->Execute($sql);

            $row =& $result->FetchRow();

            if ($row[count] < $lim) return true;
            else return false;

            break;

        case "host_php":
            $sql = "select count(*) as count from domains where host_php = 'true' and uid = '$uid'";
            $result =& $db->Execute($sql);

            $row =& $result->FetchRow();

            if ($row[count] < $lim) return true;
            else return false;

            break;

        case "host_ssl":
            $sql = "select count(*) as count from domains where host_ssl = 'true' and uid = '$uid'";
            $result =& $db->Execute($sql);

            $row =& $result->FetchRow();

            if ($row[count] < $lim) return true;
            else return false;

            break;

        case "shell_user":

            $sql = "select count(*) as count from sys_users f, domains d where uid = '$uid' and shell != '$CONF[DEFAULT_LOGIN_SHELL]'";
            $result =& $db->Execute($sql);

            $row =& $result->FetchRow();

            if ($row[count] < $lim) return true;
            else return false;

            break;

        default:

            return false;

            break;
    }
}
// A function to print " checked" if the user has that permission. We return it as a string,
// rather then printing it here, because when used in a print statement, functions are called
// before the print statement itself, and we don't want to print something like this:
// "checked <input type=checkbox name=host_php value=true>"
function perm_checked($uid, $perm)
{
    if (user_have_permission($uid, $perm)) return " checked";
}
// A function to find out if a a user has a permission
// Returns zero on no, and the number of allowed on true
function user_have_permission($uid, $perm)
{

    global $db;

    $sql = "select val, lim from user_permissions where uid = '$uid' and perm = '$perm'";
    $result =& $db->Execute($sql);

    $row =& $result->FetchRow();

    if ($row[val] == "yes") return $row[lim];
    else return 0;
}
// A function to find out if a domain id belongs to a user id
// returns true if true, or if the user is an admin.
function user_have_domain($uid, $did)
{

    global $db;

    if (is_admin()) return true;

    $sql = "select count(*) as count from domains where uid = '$uid' and id = '$did'";
    $result =& $db->Execute($sql);

    $row =& $result->FetchRow();

    if ($row[count] == 1) return true;
    else return false;
}
// A function to make a page require the user be an admin.
function req_admin()
{
    if (!is_admin())
    {
        nav_top();

        print __('You are not authorized to view this page');

        nav_bottom();

        exit;
    }
}

// A function to find out whether or not this user is an admin. Used as the primary
// security method, and is used just about everywhere

function is_admin()
{
  global $session;

  return $session->admin_user;

}
// A function to run a system command as root
function socket_cmd($cmd)
{
    global $CONF, $session_id;
    // make sure the command is safe to run
    // all the eregs are 'd out because we get some warnings sometimes that will make us unable to redirect the page
    if (@ereg("\.\.", $cmd) or @ereg("^/", $cmd) or @ereg("\;", $cmd) or @ereg('\|', $cmd) or @ereg(">", $cmd) or @ereg("<", $cmd)) die("Fatal error, unsafe command: $cmd");

    /* This code is the start of controlling slave servers. socket_cmd will accept a new argument, which
     will be the server in which to target with the command to run. Commented out for now, because it
     is not fully implemented yet.

    $query = "user=$CONF[MYSQL_ADMIN_USER]&pass=$CONF[MYSQL_ADMIN_PASS]&action=login&socket_cmd=$cmd\r\n";

$ch = curl_init("https://192.168.47.105:8000/");

curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$shell_output = curl_exec($ch);

curl_close($ch);

  */

    $shell_output = shell_exec("../sbin/wrapper $cmd");

    if ($shell_output)
    {
        // if($CONF[SERVER_TYPE] == "master") {
        $_SESSION['status_mesg'] = nl2br(rtrim($shell_output));

        /*
    } else {

      print $shell_output;

      exit;

    }
    */

    }
}

// A function that should be used at the top of every main page
function nav_top()
{
  global $js_alerts, $page_title, $sock_error, $shell_output, $session, $db, $server;

    if (isset($_SESSION['status_mesg']))
    {
        $status_mesg = $_SESSION['status_mesg'];
        $_SESSION['status_mesg'] = '';
    }

    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"><html><head>';
    // Print page title if there is one. Otherwise, print a generic title
    print '<title>';
    if ($page_title) print $page_title;
    else print "RavenCore";
    print '</title>
<meta http-equiv="Content-Type" content="text/html; charset=' . locale_getcharset() . '">
<meta name="keywords" content="ravencore, open source, control panel, hosting panel, hosting control panel, hosting software, free, webhosting software" />
<meta name="description" content="A Free and Open Source Hosting Control Panel for Linux" />
<meta name="authors" content="RavenCore" />
<meta name="owner" content="RavenCore" />
<meta name="copywrite" content="Copyright 2005 Corey Henderson">
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="./favicon.ico" type="image/x-icon">
<style type="text/css" media="screen">@import "./css/style.css";</style>
<script type="text/javascript" src="js/help_menu.js">
</script>
';
    // If the alert() function was called at all, output its contents here. We do it in the
    // <head> section of the page so that you see the error message before the page reloads
    if (sizeof($js_alerts) > 0)
    {
        print '<script type="text/javascript">';

        foreach($js_alerts as $alert) print 'alert("' . $alert . '");';

        print '</script>';
    }

    print '</head><body>';

    print '

<div id="container">

<div id="header"></div>

<table height=70% width=100%><tr>
<td valign=top>

<div id="topspan">
<span id="help_menu">&nbsp;</span>
</div>

<div id="content">';
    // only show the top menu if we're logged in
    if ($session->logged_in)
    {
        print '<ul>';
        // Admins get to see a whole lot more then normal users
        if (is_admin())
        {

	  if( ! $server->db_panic )
	    {
	      
	      print '<li class="menu"><a href="users.php" onmouseover="show_help(\'' . __('List control panel users') . '\');" onmouseout="help_rst();">' . __('Users') . ' (';
	      
	      $sql = "select count(*) as count from users";
	      $result =& $db->Execute($sql);
	      
	      $row =& $result->FetchRow();
	      
	      print $row[count];
	      
	      print ')</a></li>';
	      
	      if (have_domain_services())
		{
		  print '<li class="menu"><a href="domains.php" onmouseover="show_help(\'' . __('List domains') . '\');" onmouseout="help_rst();">' . __('Domains') . ' (';
		  
		  $sql = "select count(*) as count from domains";
		  $result =& $db->Execute($sql);
		  
		  $row =& $result->FetchRow();
		  
		  print $row[count];
		  
		  print ')</a></li>';
		}
	      
	      if (have_service("mail"))
		{
		  print '<li class="menu"><a href="mail.php" onmouseover="show_help(\'' . __('List email addresses') . '\');" onmouseout="help_rst();">' . __('Mail') . ' (';
		  
		  $sql = "select count(*) as count from mail_users";
		  $result =& $db->Execute($sql);
		  
		  $row =& $result->FetchRow();
		  
		  print $row[count];
		  
		  print ')</a></li>';
		}
	      
	      if (have_database_services())
		{
		  print '<li class="menu"><a href="databases.php" onmouseover="show_help(\'' . __('List databases') . '\');" onmouseout="help_rst();">' . __('Databases') . ' (';
		  
		  $sql = "select count(*) as count from data_bases";
		  $result =& $db->Execute($sql);
		  
		  $row =& $result->FetchRow();
		  
		  print $row[count];
		  
		  print ')</a></li>';
		}
	      
	      if (have_service("dns"))
		{
		  print '<li class="menu"><a href="dns.php" onmouseover="show_help(\'' . __('DNS for domains on this server') . '\');" onmouseout="help_rst();">' . __('DNS') . ' (';
		
		  $sql = "select count(*) as count from domains where soa is not null";
		  $result =& $db->Execute($sql);
		  
		  $row =& $result->FetchRow();
		  
		  print $row[count];
		  
		  print ')</a></li>';
		}
	      // log manager currently disabled, it broke somewhere along the line :)
	      // if(have_service("web")) print '<li class="menu"><a href="log_manager.php" onmouseover="show_help(\'View all server log files\');" onmouseout="help_rst();">Logs</a></li>';
	    }

	  print '<li class="menu"><a href="system.php" onmouseover="show_help(\'' . __('Manage system settings') . '\');" onmouseout="help_rst();">' . __('System') . '</a></li>';

        }
        else
        {
            print '<li class="menu"><a href="users.php" onmouseover="show_help(\'' . __('Goto main server index page') . '\');" onmouseout="help_rst();">' . __('Main Menu') . '</a></li>
<li class="menu"><a href="domains.php" onmouseover="show_help(\'' . __('List your domains') . '\');" onmouseout="help_rst();">' . __('My Domains') . '</a></li>';

            if (have_service("mail")) print '<li class="menu"><a href="mail.php" onmouseover="show_help(\'' . __('List all your email accounts') . '\');" onmouseout="help_rst();">' . __('My email accounts') . '</a></li>';
        }

        print '<li class="menu right"><a href="logout.php" onmouseover="show_help(\'' . __('Logout') . '\');" onmouseout="help_rst();" onclick="return confirm(\'' . __('Are you sure you wish to logout?') . '\');">' . __('Logout') . '</a></li></ul>
<hr style="visibility: hidden;">';

        print '<div><font size="2" color=red><b>' . $status_mesg . '&nbsp;</b></font></div>';

    }

}

// A function that should be used at the very bottom of every main page
function nav_bottom()
{

    ?>

</div>

</td></tr></table>

<div id="footer">

<a href="http://www.ravencore.com/">RavenCore Hosting Control Panel</a>
</div>


</div>

</body>
</html>
<?php

}

?>