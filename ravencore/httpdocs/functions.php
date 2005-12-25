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

// a function to return an array of all services installed on ravencore

function get_all_services() {

  $services = array();
  
  $h = popen("ls ../etc/services.*","r");
  
  while(!feof($h)) $data .= fread($h, 1024);
  
  pclose($h);
  
  $modules = explode("\n", $data);
  
  // get rid of the last line which is blank
  array_pop($modules);
  
  foreach($modules as $module) {

    // figure out the service name
    $service = preg_replace('|\.\./etc/services\.|','',$module);
    // only show if the +x bit is set on the conf.d file
    if(is_executable("../conf.d/$service.conf")) {
      
      $data = '';
      $tmp = array();
      
      $h = fopen($module, "r");
      
      while(!feof($h)) $data .= fread($h, 1024);
      
      pclose($h);
      
      $tmp = explode("\n", $data);
      // get rid of the last line which is blank
      array_pop($tmp);
      
      // only add services to the array we return if an init script exists for it
      foreach($tmp as $service) if(file_exists($_ENV['INITD'].'/'.$service)) array_push($services, $service);
      
    }
    
  }
  
  return $services;
  
}

//

function update_parameter($type_id, $param, $value) {

  $sql = "delete form parameters where type_id = '$type_id' and param = '$param'";
  mysql_query($sql);

  $sql = "insert into parameters set type_id = '$type_id', param = '$param', value = '$value'";
  mysql_query($sql);

}

// a basic password validation function

function valid_passwd($passwd) {

  if(function_exists("pspell_new")) {

    // we use the english dictionary
    $d = pspell_new("en");
    
    // if the string is a word, it isn't a safe password
    if(pspell_check($d, $passwd)) return false;
    
  }

  // if the string is less than 5 characters long, it isn't a safe password
  if(strlen($passwd) < 5) return false;

  return true;

}

// A function to tell us if we have any "domain" services, or in other words,
// any service that requires the use of the domains table to function

function have_domain_services() {

  // domain services as of version 0.0.1
  if(have_service("web") or have_service("mail") or have_service("dns")) return true;

  return false;

}

// A function to tell us whether or not we have database services.
// Right now it only looks for mysql, but this will in the future look for other db types
// such as pgsql, mssql, etc

function have_database_services() {

  if(have_service("mysql")) return true;
  else return false;

}

function have_service($service) {

  global $CONF;

  if(is_executable("$CONF[RC_ROOT]/conf.d/$service.conf")) return true;
  else return false;

}

// A function that requires the existances of the webserver to load the page
// Must use before you output any headers.

function req_service($service) {

  if(!have_service($service)) {

    nav_top();

    print 'This server does not have ' . $service . ' installed. Page cannot be displayed.';

    nav_bottom();

    exit;

  }

}

// A function to return the number of domains by a given uid

function num_domains($uid) {

  $sql = "select count(*) as count from domains where uid = '$uid'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  return $row[count];

}

// Get the disk space usage for a user during the given month/year, by grabbing
// all of his domains and running the domain_space_usage function on each one,
// and totalling the results. 

function user_space_usage($uid, $month, $year) {

  $total = 0;

  $sql = "select id from domains where uid = '$uid'";
  $result = mysql_query($sql);

  while( $row = mysql_fetch_array($result) ) $total += domain_space_usage($row[id], $month, $year);

  return $total;

}

// Do the same as user_space_usage, except mesure traffic instead

function user_traffic_usage($uid, $month, $year) {

  $total = 0;

  $sql = "select id from domains where uid = '$uid'";
  $result = mysql_query($sql);

  while( $row = mysql_fetch_array($result) ) $total += domain_traffic_usage($row[id], $month, $year);

  return $total;

}

// A function that returns the amount of space ( in megs ) a domain has used
// in the given month/year

function domain_space_usage($did, $month, $year) {

  $total = 0;

  $arr[0] = "web";
  $arr[1] = "mail";
  $arr[2] = "database";
  
  foreach($arr as $type) {

    $sql = "select * from domain_space where did = '$did' and type = '$type' and month(date) = '$month' and year(date) = '$year' order by date desc limit 1";
    $result = mysql_query($sql);

    $row = mysql_fetch_array($result);

    $total += $row[bytes];

  }

  return ereg_replace(",","",number_format($total / 1024 / 1024, 2));


}

// A function to return the amount of traffic ( in megs ) used in the given month/year

function domain_traffic_usage($did, $month, $year) {

  global $CONF;

  $domain_name = get_domain_name($did);

  //$prog = "awk '/^BEGIN_DOMAIN/ { getline; print $4 }' " . $CONF[RC_ROOT] . "/var/lib/awstats/awstats" . $month . $year . "." . $domain_name . ".txt";
  //$prog = "awk '/^BEGIN_DOMAIN/ { while(1) { getline; if($1 == \"END_DOMAIN\") exit; print $4 } }' " . $CONF[RC_ROOT] . "/var/lib/awstats/awstats" . $month . $year . "." . $domain_name . ".txt";

  $dir = $CONF[VHOST_ROOT] . '/' . $domain_name . '/var/awstats/awstats' . $month . $year . '.' . $domain_name . '.txt';
  
  if(file_exists($dir)) {
    
    $prog = 'total=0; for i in `awk \'/^BEGIN_DOMAIN/ { while(1) { getline; if($1 == "END_DOMAIN") break; else print $4; } }\' ' . $dir . '`; do total=$(expr $i "+" $total); done; for i in `awk \'/^BEGIN_ROBOT/ { while(1) { getline; if($1 == "END_ROBOT") break; else print $3; } }\' ' . $dir . '`; do total=$(expr $i "+" $total); done; for i in `awk \'/^BEGIN_ERRORS/ { while(1) { getline; if($1 == "END_ERRORS") break; else print $3; } }\' ' . $dir . '`; do total=$(expr $i "+" $total); done; echo $total';
    
    $handle = popen($prog,'r');

    while( !feof($handle) ) $prog_data .= fread($handle, 1024);
    
    pclose($handle);
    
  } else $prog_data = 0;

  $sql = "select sum(bytes) as traffic from domain_traffic t, domains d where did = d.id and month(date) = '$month' and year(date) = '$year' and d.id = '$did'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  $prog_data += $row[traffic];

  return ereg_replace(",","",number_format( $prog_data / 1024 / 1024, 2));

}

// Returns an array, first element is the base directory and second element is
// the rest of the directory. This is mainly used in the filemanager

function working_directory($did, $dir) {

  global $CONF;

  $domain_name = get_domain_name($did);

  // the admin user has special privs, allowing him to browse everything on the server

  if(is_admin()) {

    // we needs to build the base_dir according to whether or not we have a domain ID value ( did )
    // so that we know if we're working in someone's space

    if($did) $base_dir = "$CONF[VHOST_ROOT]/$domain_name";
    else {

      //if we are in a domain directory, edit out the VHOST_ROOT/DOMAIN and set the did
      if(ereg("$CONF[VHOST_ROOT]/.*",$dir)) {

        $domain_name = ereg_replace("$CONF[VHOST_ROOT]/","","$dir");

        $tmp = explode("/",$domain_name);

        $domain_name = $tmp[0];

        array_shift($tmp);

        $base_dir = $CONF[VHOST_ROOT] . "/" . $domain_name;
        $dir = "";

        foreach($tmp as $val) $dir .= "/$val";

        $sql = "select id from domains where name = '$domain_name'";
        $result = mysql_query($sql);

        $row = mysql_fetch_array($result);

        $did = $row[id];

      }

    }

  } else $base_dir = "$CONF[VHOST_ROOT]/$domain_name";

  $arr[0] = $base_dir;
  $arr[1] = $dir;
  $arr[2] = $did;

  return $arr;

}

// A function to 

function get_file_content($did, $file) {
  
  if(!$did and !is_admin()) return false;

  return file_get_contents($file);

}

//

function file_perms($did, $file) {

  $attr = stat($file);

  $attr_usr = posix_getpwuid($attr[uid]);

  $attr_grp = posix_getgrgid($attr[gid]);

  $perms = fileperms($file);

  $sql = "select count(*) as count from sys_users where did = '$did' and login = '$attr_usr[name]'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  //find out if the user owns the file
  if($row[count] == 1) {

    if($perms & 0x0100) $can_read = true;
    else $can_read = false;
    if($perms & 0x0080) $can_write = true;
    else $can_write = false;
    
    //else find if the user is in the group
  } else if(in_array($attr_usr[name], $attr_grp[members])) {
      
    if($perms & 0x0020) $can_read = true;
    else $can_read = false;
    if($perms & 0x0010) $can_write = true;
    else $can_write = false;
    
    //else use the world permissions
  } else {
    
    if($perms & 0x0004) $can_read = true;
    else $can_read = false;
    if($perms & 0x0002) $can_write = true;
    else $can_write = false;
    
  }

  $arr[0] = $can_read;
  $arr[1] = $can_write;

  return $arr;
  
}

/*
This function is written from code taken from:
http://www.php.net/manual/en/function.fileperms.php
*/

function file_perms_str($file) {

  $perms = fileperms($file);

  if (($perms & 0xC000) == 0xC000) {
    // Socket
    $info = 's';
  } elseif (($perms & 0xA000) == 0xA000) {
    // Symbolic Link
    $info = 'l';
  } elseif (($perms & 0x8000) == 0x8000) {
    // Regular
    $info = '-';
  } elseif (($perms & 0x6000) == 0x6000) {
    // Block special
    $info = 'b';
  } elseif (($perms & 0x4000) == 0x4000) {
    // Directory
    $info = 'd';
  } elseif (($perms & 0x2000) == 0x2000) {
    // Character special
    $info = 'c';
  } elseif (($perms & 0x1000) == 0x1000) {
    // FIFO pipe
    $info = 'p';
  } else {
    // Unknown
    $info = 'u';
  }

  // Owner
  $info .= (($perms & 0x0100) ? 'r' : '-');
  $info .= (($perms & 0x0080) ? 'w' : '-');
  $info .= (($perms & 0x0040) ?
	    (($perms & 0x0800) ? 's' : 'x' ) :
	    (($perms & 0x0800) ? 'S' : '-'));

  // Group
  $info .= (($perms & 0x0020) ? 'r' : '-');
  $info .= (($perms & 0x0010) ? 'w' : '-');
  $info .= (($perms & 0x0008) ?
	    (($perms & 0x0400) ? 's' : 'x' ) :
	    (($perms & 0x0400) ? 'S' : '-'));

  // World
  $info .= (($perms & 0x0004) ? 'r' : '-');
  $info .= (($perms & 0x0002) ? 'w' : '-');
  $info .= (($perms & 0x0001) ?
	    (($perms & 0x0200) ? 't' : 'x' ) :
	    (($perms & 0x0200) ? 'T' : '-'));

  return $info;

}

// A function to convert the number of bytes into K or MB

function readable_size($size) {

  if($size > 1048576) {

    $size /= 1048576;
    $size = round($size,2) . 'MB';

  } else if($size = round(($size / 1024),2)) $size .= 'K';

  return $size;

}

// A function to delete a domain's log file

function delete_log($did, $log_file) {

  $domain_name = get_domain_name($did);

  socket_cmd("log_del $domain_name $log_file");

}

// A function to return the user id of the domain given

function get_uid_by_did($did) {

  $sql = "select u.id from users u, domains d where d.id = '$did' and d.uid = u.id";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  return $row[id];

}

// A function to get a domain's did from its name

function get_domain_id($domain) {

  $sql = "select name from domains where name = '$domain'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  return $row[id];

}

// A function to get a domain's name from its did

function get_domain_name($did) {

  $sql = "select name from domains where id = '$did'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  return $row[name];

}

// A function to tell us whether or not given string is an ip address. I got the core
// routines off of php.net, but made some of my own changes to make it return a bool value

function is_ip($ip) {
  $ip = trim($ip);
  if (strlen($ip) < 7) return false;
  if (!ereg("\.",$ip)) return false;
  if (!ereg("[0-9.]{" . strlen($ip) . "}",$ip)) return false;
  $ip_arr = split("\.",$ip);
  if (count($ip_arr) != 4) return false;
  for ($i=0;$i<count($ip_arr);$i++) {
    if ((!is_numeric($ip_arr[$i])) || (($ip_arr[$i] < 0) || ($ip_arr[$i] > 255))) return false;
  }
  return true;
}

// A function to queue a message to be output with a javascript alert
// It must be called before the nav_top function

function alert($message) {

  global $js_alerts;

  array_push($js_alerts, $message);
  
}

// A function to do a header to the given location. Must be called before output
// goes to the browser. This function is used just about everywhere.

function goto($url) {

  // session variables may not be saved before the browser changes to the new page, so we need to
  // save them here
  session_write_close();

  header("Location: $url");

  exit;

}

// A function to try to restart the mysql server if we failed to get a connection

function mysql_panic() {

  print "Unable to connect to DB server! Attempting to restart mysql <br><b>";

  socket_cmd("mysql_restart");

  // while we have a restart lockfile, hang

  do {

    print ".<br>";

    sleep(1);

  } while( file_exists("/tmp/mysql_restart.lock") );

  print "</b>Restart command completed. Please refresh the page.<p>If the problem persists, contact the system administrator";

  exit;

}

// Returns the correct word assositated with a permission

function perm_into_word($perm) {

  switch($perm) {
    
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

function user_can_add($uid, $perm) {

  global $CONF;

  $lim = user_have_permission($uid, $perm);
  
  if(!$lim) return false;
  if($lim < 0) return true;

  switch($perm) {

  case "domain":
    $sql = "select count(*) as count from domains where uid = '$uid'";
    $result = mysql_query($sql);

    $row = mysql_fetch_array($result);

    if($row[count] < $lim) return true;
    else return false;

    break;

  case "database":
    $sql = "select count(*) as count from data_bases b, domains d where did = d.id and uid = '$uid'";
    $result = mysql_query($sql);

    $row = mysql_fetch_array($result);

    if($row[count] < $lim) return true;
    else return false;

    break;

  case "crontab":
    // NEED TO RE-DO CRONTAB MANAGEMENT
    break;

  case "email":
    $sql = "select count(*) as count from mail_users m, domains d where did = d.id and uid = '$uid'";
    $result = mysql_query($sql);

    $row = mysql_fetch_array($result);

    if($row[count] < $lim) return true;
    else return false;
    
    break;

  case "dns_rec":
    $sql = "select count(*) as count from dns_rec r, domains d where did = d.id and uid = '$uid'";
    $result = mysql_query($sql);

    $row = mysql_fetch_array($result);

    if($row[count] < $lim) return true;
    else return false;

    break;

  case "host_cgi":
    $sql = "select count(*) as count from domains where cgi = 'true' and uid = '$uid'";
    $result = mysql_query($sql);

    $row = mysql_fetch_array($result);

    if($row[count] < $lim) return true;
    else return false;

    break;

  case "host_php":
    $sql = "select count(*) as count from domains where php = 'true' and uid = '$uid'";
    $result = mysql_query($sql);

    $row = mysql_fetch_array($result);

    if($row[count] < $lim) return true;
    else return false;

    break;

  case "host_ssl":
    $sql = "select count(*) as count from domains where ssl = 'true' and uid = '$uid'";
    $result = mysql_query($sql);

    $row = mysql_fetch_array($result);
    
    if($row[count] < $lim) return true;
    else return false;
    
    break;

  case "shell_user":
    $sql = "select count(*) as count from sys_users f, domains d where did = d.id and uid = '$uid' and shell != '$CONF[DEFAULT_LOGIN_SHELL]'";
    $result = mysql_query($sql);

    $row = mysql_fetch_array($result);

    if($row[count] < $lim) return true;
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

function perm_checked($uid, $perm) {

  if(user_have_permission($uid, $perm)) return " checked";

}

// A function to find out if a a user has a permission
// Returns zero on no, and the number of allowed on true

function user_have_permission($uid, $perm) {

  $sql = "select val, lim from user_permissions where uid = '$uid' and perm = '$perm'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  if($row[val] == "yes") return $row[lim];
  else return 0;

}

// A function to find out if a domain id belongs to a user id
// returns true if true, or if the user is an admin.

function user_have_domain($uid, $did) {

  if(is_admin()) return true;

  $sql = "select count(*) as count from domains where uid = '$uid' and id = '$did'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  if($row[count] == 1) return true;
  else return false;

}

// A function to make a page require the user be an admin.

function req_admin() {

  if(!is_admin()) {

    nav_top();

    print 'You are not authorized to view this page';

    nav_bottom();

    exit;

  }

}

// A function to find out whether or not this user is an admin. Used as the primary
// security method, and is used just about everywhere

function is_admin() {

  global $row_session, $CONF;

  // the $row_user array is only set if we are not the admin user. The variable is set
  // in the auth.php file

  if($row_session[login] == $CONF[MYSQL_ADMIN_USER]) return true;
  else return false;

}

// A function to run a system command as root

function socket_cmd($cmd) {
  
  global $CONF, $session_id;

  //make sure the command is safe to run
  //all the eregs are @'d out because we get some warnings sometimes that will make us unable to redirect the page
  if(@ereg("\.\.",$cmd) or @ereg("^/",$cmd) or @ereg("\;",$cmd) or @ereg('\|',$cmd) or @ereg(">",$cmd) or @ereg("<",$cmd)) die("Fatal error, unsafe command: $cmd");

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

  if($shell_output) {

    //if($CONF[SERVER_TYPE] == "master") {
      
    $_SESSION['status_mesg'] = nl2br(rtrim($shell_output));

    /*  
    } else {
      
      print $shell_output;
      
      exit;

    }
    */

  }

}

// A function to delete the hosting for a domain.

function delete_hosting($did) {

  $sql = "select * from domains where id = '$did'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num != 0) {

    $row = mysql_fetch_array($result);

    $sql = "select login from sys_users u, domains d where u.id = d.suid and d.id = '$did'";
    $result = mysql_query($sql);

    while ( $row_ftp = mysql_fetch_array($result) ) {

      $sql = "delete from sys_users where login = '$row_ftp[login]'";
      mysql_query($sql);

      socket_cmd("ftp_del $row_ftp[login]");

    }

    $sql = "update domains set host_type = 'none' where id = '$did'";
    mysql_query($sql) or die(mysql_error());

    socket_cmd("domain_del $row[name]");

  }

}

function delete_user($uid) {

  $sql = "select * from users where id = '$uid'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num != 0) {

    $row = mysql_fetch_array($result);

    $sql = "select * from domains where uid = '$uid'";
    $result = mysql_query($sql);

    while( $row_domain = mysql_fetch_array($result) ) delete_domain($row_domain[id]);

    $sql = "delete from users where id = '$uid'";
    mysql_query($sql);

  }

}

// A function to delete a domain, it's files, it's email, dns... the whole 9 yards.
// Use with caution!

function delete_domain($did) {

  $sql = "select * from domains where id = '$did'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num != 0) {

    $row = mysql_fetch_array($result);

    $sql = "delete from domains where id = '$row[id]'";
    mysql_query($sql);

    $sql = "delete from mail_users where did = '$row[id]'";
    mysql_query($sql);

    $sql = "delete from dns_rec where did = '$row[id]'";
    mysql_query($sql);

    $sql = "select login from sys_users where did = '$row[id]'";
    $result = mysql_query($sql);

    while( $row_ftp = mysql_fetch_array($result) ) {

      $sql = "delete from sys_users where did = '$row[id]'";
      mysql_query($sql);
      
      socket_cmd("ftp_del $row_ftp[login]");

    }

    socket_cmd("domain_del $row[name]");

    socket_cmd("rehash_named --rebuild-conf --all");

  }

}

// A function to remove an email address

function delete_email($email) {

  if(!have_service("mail")) return;

  $sql = "select d.name, m.mail_name from mail_users m, domains d where did = d.id and m.id = '$email'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num != 0) {

    $row = mysql_fetch_array($result);

    $sql = "delete from mail_users where id = '$email'";
    mysql_query($sql);

    socket_cmd("mail_del $row[name] $row[mail_name]");

    socket_cmd("rehash_mail --all");

  }

}

// A function that should be used at the top of every main page

function nav_top() {

  global $js_alerts, $page_title, $sock_error, $shell_output, $row_user, $row_session;

  if($_SESSION['status_mesg']) {
    
    $status_mesg = $_SESSION['status_mesg'];
    $_SESSION['status_mesg'] = '';

  }

  print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"><html><head>';

  // Print page title if there is one. Otherwise, print a generic title
  
  print '<title>';
  if($page_title) print $page_title;
  else print "RavenCore";
  print '</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="icon" href="/favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<style type="text/css" media="screen">@import "/css/style.css";</style>
<script type="text/javascript" src="js/help_menu.js">
</script>
';

  // If the alert() function was called at all, output its contents here. We do it in the
  // <head> section of the page so that you see the error message before the page reloads
  
  if(sizeof($js_alerts) > 0) {
    
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

 if($row_session) {
   
   print '<ul>';
   
   // Admins get to see a whole lot more then normal users
   
   if(is_admin()) {
     
     print '<li class="menu"><a href="users.php" onmouseover="show_help(\'List control panel users\');" onmouseout="help_rst();">Users (';
     
     $sql = "select count(*) as count from users";
     $result = mysql_query($sql);
     
     $row = mysql_fetch_array($result);
     
     print $row[count];
     
     print ')</a></li>';
     
     if(have_domain_services()) {
       
       print '<li class="menu"><a href="domains.php" onmouseover="show_help(\'List domains\');" onmouseout="help_rst();">Domains (';
       
       $sql = "select count(*) as count from domains";
       $result = mysql_query($sql);
       
       $row = mysql_fetch_array($result);
       
       print $row[count];
       
       print ')</a></li>';
       
     }
     
     if(have_service("mail")) {
       
       print '<li class="menu"><a href="mail.php" onmouseover="show_help(\'List email addresses\');" onmouseout="help_rst();">Mail (';
       
       $sql = "select count(*) as count from mail_users";
       $result = mysql_query($sql);
       
       $row = mysql_fetch_array($result);
       
       print $row[count];
       
       print ')</a></li>';
       
     }
     
     if(have_database_services()) {
       
       print '<li class="menu"><a href="databases.php" onmouseover="show_help(\'List databases\');" onmouseout="help_rst();">Databases (';
       
      $sql = "select count(*) as count from data_bases";
      $result = mysql_query($sql);
      
      $row = mysql_fetch_array($result);
      
      print $row[count];
      
      print ')</a></li>';
      
     }
     
     if(have_service("dns")) {
       
       print '<li class="menu"><a href="dns.php" onmouseover="show_help(\'DNS for domains on this server\');" onmouseout="help_rst();">DNS (';
       
       $sql = "select count(*) as count from domains where soa is not null";
       $result = mysql_query($sql);
       
       $row = mysql_fetch_array($result);
       
       print $row[count];
       
       print ')</a></li>';
       
     }
     
     // log manager currently disabled, it broke somewhere along the line :)
     //if(have_service("web")) print '<li class="menu"><a href="log_manager.php" onmouseover="show_help(\'View all server log files\');" onmouseout="help_rst();">Logs</a></li>';
     
     print '<li class="menu"><a href="system.php" onmouseover="show_help(\'Manage system settings\');" onmouseout="help_rst();">System</a></li>';
     
   } else if($row_user) {
     
     print '<li class="menu"><a href="users.php" onmouseover="show_help(\'Goto main server index page\');" onmouseout="help_rst();">Main Menu</a></li>
<li class="menu"><a href="domains.php" onmouseover="show_help(\'List your domains\');" onmouseout="help_rst();">My Domains</a></li>';
     
    if(have_service("mail")) print '<li class="menu"><a href="mail.php" onmouseover="show_help(\'List all your email accounts\');" onmouseout="help_rst();">My email accounts</a></li>';
    
   }
   
   print '<li class="menu right"><a href="logout.php" onmouseover="show_help(\'Logout\');" onmouseout="help_rst();" onclick="return confirm(\'Are you sure you wish to logout?\');">Logout</a></li></ul>
<hr style="visibility: hidden;">';
   
   print '<div><font size="2" color=red><b>' . $status_mesg . '&nbsp;</b></font></div>';
   
 }

}
 
 // A function that should be used at the very bottom of every main page
 
 function nav_bottom() {
   
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

// A function to read in our base configuration variables

function read_db_conf() {

  global $CONF;

  // Open configuration files and read in all the data. We use popen because we cat out all
  // of the files, for simplicity.
  
  $handle = popen("cat {/etc/ravencore.conf,../database.cfg}","r");

  while( !feof($handle) ) $conf_data .= fread($handle, 1024);
  
  pclose($handle);
  
  // Seperate the data line by line
  
  $conf_array = explode("\n", $conf_data);
  
  foreach($conf_array as $line) {
    
    // Get rid of quotation marks
    
    $line = ereg_replace("\"", "", $line);
    $line = ereg_replace("\'", "", $line);

    // If this looks like a bash shell variable, make it into a php variable. All conf
    // variables will be all upper case. If they are not, they won't get read in here.
    
    if(preg_match("/^[A-Z||_]*=/", $line)) {
      
      $var_name = ereg_replace("=.*", "", $line);
      
      $var_value = ereg_replace(".*=", "", $line);

      // Set the conf array with the name and value of this variable
      
      $CONF[$var_name] = $var_value;
      
    }
    
  }
  
  // Read in the file containing the mysql database password

  $CONF[MYSQL_ADMIN_PASS] = shell_exec("/bin/cat ../.shadow");

  // Get rid of whitespace and return characters
  $CONF[MYSQL_ADMIN_PASS] = trim($CONF[MYSQL_ADMIN_PASS]);

}

// A function to read in our database configuration variables

function read_conf() {

  global $CONF, $conf_not_complete;

  // get our settings from the database

  $sql = "select * from settings";
  $result = mysql_query($sql);
  
  while( $row = mysql_fetch_array($result) ) {

    $key = $row[setting];
    $val = $row[value];

    // load the info into the global CONF array

    $CONF[$key] = $val;

  }

  // Open configuration files and read in all the data. We use popen because we cat out all
  // of the files, for simplicity.

  // The php is the only place in which the server_type.conf is read, because the php is the
  // only place where the value matters at this point.
  
  $handle = popen("cat \$(for i in `ls ../conf.d/`; do if [ -x ../conf.d/\$i ]; then echo ../conf.d/\$i; fi; done | tr '\n' ' ') ../etc/server_type.conf} 2> /dev/null","r");

  while( !feof($handle) ) $conf_data .= fread($handle, 1024);
  
  pclose($handle);
  
  // Seperate the data line by line
  
  $conf_array = explode("\n", $conf_data);

  foreach($conf_array as $line) {
    
    // Get rid of quotation marks
    
    $line = ereg_replace("\"", "", $line);
    $line = ereg_replace("\'", "", $line);

    // If this looks like a bash shell variable, make it into a php variable. All conf
    // variables will be all upper case. If they are not, they won't get read in here.
    
    if(preg_match("/^[A-Z||_]*=/", $line)) {

      // the name of the variable
      
      $var_name = ereg_replace("=.*", "", $line);
      
      // the shipped default of the variable

      $var_value = ereg_replace(".*=", "", $line);

      // check to make sure we have this variable

      if(!$CONF[$var_name] and $var_name != "SERVER_TYPE") {

	$conf_not_complete = true;

      } else if($var_name == "SERVER_TYPE") $CONF[$var_name] = $var_value;

    }
    
  }

  // get this version number
  
  $handle = fopen("../etc/version","r");

  while( !feof($handle) ) $version_data .= fread($handle, 1024);

  fclose($handle);

  $CONF[VERSION] = trim($version_data);

}

?>
