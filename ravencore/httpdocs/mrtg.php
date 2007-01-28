<?php

include "auth.php";

req_admin();

if( $_GET['img'] )
{

  header('Content-type: image/png');

  print $db->do_raw_query('mrtg image ' . $_GET['img']);

}
else
{

  print $db->do_raw_query('mrtg html');

}

?>