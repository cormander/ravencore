#!/bin/bash

cat << EOF > ravencore/var/apps/squirrelmail/src/redirect.php.new
<?php

session_start();

if(\$_SESSION['login_username'] and \$_SESSION['secretkey']) {

  \$_POST['login_username'] = \$_SESSION['login_username'];
  \$_POST['secretkey'] = \$_SESSION['secretkey'];
  \$_POST['js_autodetect_results'] = 0;
  \$_POST['just_logged_in'] = 1;

}

session_destroy();

EOF

cat ravencore/var/apps/squirrelmail/src/redirect.php | sed '1d' >> ravencore/var/apps/squirrelmail/src/redirect.php.new

mv -f ravencore/var/apps/squirrelmail/src/redirect.php.new ravencore/var/apps/squirrelmail/src/redirect.php
