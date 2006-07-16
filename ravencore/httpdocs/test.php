<?php

$CONF['RC_ROOT'] = '/usr/local/ravencore';

include 'classes/dbsock.php';

$db = new dbsock;

//print '<pre>' . $db->run_cmd("rehash_httpd ");
//print $db->data_auth("asdf");

//$db->use_database("mysql");

print $db->run_cmd('rehash_httpd ' . "\r" . '/bin/ls');

exit;

if ( ! $db->str_to_bool($db->do_raw_query('passwd asdf asdfasdf')) )
{
  print 'blah';
}

exit;

$sql = "select * from user";

$result = $db->data_query($sql);

$sql = "select * from usersa";

$result = $db->data_query($sql);

print '<pre>mark';

while( $row = $db->data_fetch_array($result) ) print_r($row);

print "\n\n";

//$db->use_database("mysql");

print "\n\n";

$sql = "update users set company = 'blah'";

$db->data_query($sql);

$sql = "insert into users set company = 'blah'a";

$db->data_query($sql);


?>