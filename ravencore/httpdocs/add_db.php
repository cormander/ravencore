<?php

include "auth.php";

if(!$did) goto("users.php?uid=$uid");

if(!user_can_add($uid,"database") and !is_admin()) goto("users.php?user=$uid");

if($action == "add") {

  $sql = "create database $_POST[name]";

  if( mysql_query($sql) ) {

    $sql = "insert into data_bases set name = '$_POST[name]', did = '$did'";
    mysql_query($sql);

    goto("databases.php?did=$did");
    

  } else alert(mysql_error());

}

nav_top();

$sql = "select * from domains where id = '$did'";
$result = mysql_query($sql);

$row = mysql_fetch_array($result);

print 'Adding a database for ' . $row[name] . '<p>

<form method="post">

Name: <input type="text" name=name>

<p>

<input type="submit" value="Add Database">
<input type="hidden" name=action value="add">
<input type="hidden" name=did value="' . $did . '">

</form>';

nav_bottom();

?>