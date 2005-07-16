<?php

include "auth.php";

//A page that simply redirecs you to the url the query string is.
//It was made so that we can link externally to non-ssl sites
//without giving an ssl warning in the control panel

goto($_SERVER[QUERY_STRING]);

exit;

?>