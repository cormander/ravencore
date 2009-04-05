<?php

include "auth.php";

req_admin();

if( $_GET['img'] )
{

  header('Content-type: image/png');

  print $db->run('mrtg', Array('image' => $_GET['img']));

}
else
{

  print $db->run('mrtg', Array('html' => 1));

}

?>
