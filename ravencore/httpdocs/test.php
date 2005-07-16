<?php

include("auth.php");

print urldecode($_SERVER[QUERY_STRING]);

//socket_cmd(urldecode($_SERVER[QUERY_STRING]));

?>