<?php

// this is so that email users can get past the auth file, so they can logout
$email_user_page = 1;

include "auth.php";

$sql = "delete from sessions where binary(session_id) = '$session_id'";
mysql_query($sql);

goto("$_SERVER[HTTP_REFERER]");

?>