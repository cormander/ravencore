<?php

include "auth.php";

if($action == "userdel") {

  $sql = "select * from data_base_users where id = '$dbu'";
  mysql_query($sql);

  $row = mysql_fetch_array($result);

  mysql_select_db("mysql",$link) or die("Unable to use mysql database");

  $sql = "delete from user where User = '$row[login]'";

  if( mysql_query($sql) ) {

    mysql_select_db($CONF[MYSQL_ADMIN_DB],$link);

    $sql = "delete from data_base_users where id = '$dbu'";
    mysql_query($sql);

    goto("databases.php?did=$did&db=$db");

  } else alert("Unable to delete the user $row[login]");

} else if($action == "dbdel") {

  $sql = "select * from data_bases where id = '$db'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  if(!$row) $error = "That database does not exist";
  else {

    $sql = "drop database $row[name]";
    
    if( mysql_query($sql) ) {

      $sql = "delete from data_bases where id = '$db'";
      mysql_query($sql);

      $sql = "select * from data_base_users where db_id = '$db'";
      $result = mysql_query($sql);

      mysql_select_db("mysql",$link) or die("Unable to use mysql database");

      while( $row = mysql_fetch_array($result) ) {

	$sql = "delete from user where User = '$row[login]'";
	mysql_query($sql);
	
      }

      goto("databases.php?did=$did");

    } else alert(mysql_error());

  }
}

if(!$db and $did) {

  nav_top();

  $sql = "select * from domains where id = '$did'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);

  if(user_can_add($user,"database") or is_admin()) print '<a href="add_db.php?did=' . $did . '">Add a Database</a><br /><br />';

  $sql = "select * from data_bases where did = '$did'";
  $result = mysql_query($sql);
  
  $num = mysql_num_rows($result);
  
  if($num == 0) print "No databases setup";
  else print '<table><tr><th colspan="2">Databases for <a href="domains.php?did=' . $did . '">' . $row[name] . '</a></th></tr>';

  while ( $row = mysql_fetch_array($result) ) {
    
    print '<tr><td><a href="databases.php?did=' . $did . '&db=' . $row[id] . '">' . $row[name] . '</a></td><td><a href="databases.php?action=dbdel&db=' . $row[id] . '&did=' . $did . '" onclick="return confirm(\'Are you sure you wish to delete this database?\');">delete</a></td></tr>';
    
  }
  
  if($num != 0) print '</table>';

} else if($db and $did) {

  nav_top();

  $sql = "select * from data_bases where id = '$db'";
  $result = mysql_query($sql);

  $row = mysql_fetch_array($result);
  
  print 'Users for the <a href="databases.php?did=' . $did . '">database</a> ' . $row[name] . ' - <a href="add_db_user.php?did=' . $did . '&db=' . $db . '">Add a database user</a><p>';
  
  $sql = "select * from data_base_users where db_id = '$db'";
  $result = mysql_query($sql);

  $num = mysql_num_rows($result);

  if($num == 0) print "No users for this database<p>";

  while( $row = mysql_fetch_array($result) ) {

    print $row[login] . ' - <a href="phpmyadmin.php?did=' . $did . '&dbu=' . $row[id] . '&db=' . $db . '" target=_blank>phpMyAdmin</a> - <a href="databases.php?action=userdel&dbu=' . $row[id] . '&did=' . $did . '&db=' . $db . '" onclick="return confirm(\'Are you sure you wish to delete this database user?\');">delete</a><p>';
    
  }

  print "Note: You may only manage one database user at a time with the phpmyadmin";
    
} else {

  //list add databases

  req_admin();
  
  nav_top();

  print '<form method=get name=search>Search: <input type=text name=search value="' . $_GET[search] . '">
<input type=submit value=Go onclick="if(!document.search.search.value) { alert(\'Please enter in a search value!\'); return false; }">';
  
  if($_GET[search]) print ' <input type=button value="Show All" onclick="self.location=\'databases.php\'">';
  
  print '</form><p>';
  
  $sql = "select * from data_bases";
  if($_GET[search]) $sql .= " where name like '%$_GET[search]%'";
  $sql .= " order by name";
  $result = mysql_query($sql);
  
  $num = mysql_num_rows($result);

  if($num == 0 and !$_GET[search]) print "There are no databases setup";
  else if($_GET[search]) print 'Your search returned <i><b>' . $num . '</b></i> results<p>';

  while( $row = mysql_fetch_array($result) ) {

    print '<a href="databases.php?db=' . $row[id] . '&did=' . $row[did] . '">' . $row[name] . '</a><br>';

  }

}

nav_bottom();

?>
