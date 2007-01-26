#!/bin/bash

cat ravencore/var/apps/phpmyadmin/libraries/config.default.php | \
    sed "s|// \$cfg\['Lang'\] *= 'en-iso-8859-1';|\$cfg\['Lang'\] = \$_SESSION['ravencore_phpmyadmin_lang'];|" | \
    sed "s|\['user'\] *= 'root'|['user'] = \$_SESSION['ravencore_login']|" | \
    sed "s|\['password'\] *= ''|['password'] = \$_SESSION['ravencore_passwd']|" | \
    sed "s|\['only_db'\] *= ''|['only_db'] = \$_SESSION['ravencore_name']|" > \
    ravencore/var/apps/phpmyadmin/config.inc.php

perl -pi -e "s/'_SESSION',/'_SESSION','rcdb',/" ravencore/var/apps/phpmyadmin/libraries/common.lib.php

perl -pi -e "s/ini_set\('session.save_handler', 'files'\);//" ravencore/var/apps/phpmyadmin/libraries/session.inc.php
