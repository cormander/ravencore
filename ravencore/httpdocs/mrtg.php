<?php

include "auth.php";

req_admin();

if( $_GET['img'] )
{

  header('Content-type: image/png');

  print $db->run('mrtg image ' . $_GET['img']);

}
else
{

  print $db->run('mrtg html');

}

?>
