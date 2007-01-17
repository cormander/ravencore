<?php

include "auth.php";

req_admin();

if( $_GET['img'] )
{

  $h = fopen('../var/log/mrtg/' . $_GET['img'], 'r' );

  while( ! feof($h) ) $data .= fread($h, 1024);
  
  fclose($h);

  header('Content-type: image/png');

  print $data;

  rc_exit();

}

$dir = dir('../var/log/mrtg');

while (false !== ($entry = $dir->read())) {
  // if this element ends in .html, include it
  if( ereg('\.html$',$entry) )
    {

      print preg_replace('|SRC="([a-zA-Z0-9_\-\.]*)"|i','SRC="?img=\1"',file_get_contents('../var/log/mrtg/'.$entry));

    }
}

?>