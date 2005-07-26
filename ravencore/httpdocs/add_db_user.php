<?php

include "auth.php";

if(!$db or !$did) goto("users.php?user=$uid");

if($action == "add") {

  // does user already exist?
  $sql = "select count(*) as count from data_base_users where login = '$_POST[login]'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  // we can't use the admin username
  if($_POST[login] == $CONF[MYSQL_ADMIN_USER]) $row[count] = 1;

  if($row[count] != 0) $error = "User $_POST[login] already exists";
  else {

    if(preg_match('/^'.REGEX_PASSWORD.'$/',$_POST[passwd]) and valid_passwd($_POST[passwd])) {

      $sql = "select * from data_bases where id = '$db'";
      $result = mysql_query($sql);
      
      $row = mysql_fetch_array($result);
      
      $sql = "grant select,insert,update,delete,create,drop,alter on $row[name].* to $_POST[login]@localhost identified by '$_POST[passwd]'";
      
      if( mysql_query($sql) ) {
	
	$sql = "insert into data_base_users set login = '$_POST[login]', db_id = '$db', passwd = '$_POST[passwd]'";
	mysql_query($sql);
	
	goto("databases.php?did=$did&db=$db");
	
      } else alert(mysql_error());

    } else {

      alert("Invalid password. Must only contain letters and numbers, must be atleast 5 characters, and not a dictionary word");
      $_POST[passwd] = "";

    }

  }

}

nav_top();

$sql = "select * from data_bases where id = '$db'";
$result = mysql_query($sql);

$row = mysql_fetch_array($result);

print 'Adding a user for database ' . $row[name] . '<p>

<form method="post">

Login: <input type="text" name=login>
<p>
Password: <input type="password" name=passwd>
<p>

<input type="submit" value="Add User">
<input type="hidden" name=action value="add">
<input type="hidden" name=did value="' . $did . '">
<input type="hidden" name=db value="' . $db . '">

</form>';

nav_bottom();

?>