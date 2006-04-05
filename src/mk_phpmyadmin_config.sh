#!/bin/bash

cat ravencore/var/apps/phpmyadmin/libraries/config.default.php | \
    sed "s|// \$cfg\['Lang'\] *= 'en-iso-8859-1';|\$cfg\['Lang'\] = \$_SESSION['phpmyadmin_lang'];|" | \
    sed "s|\['user'\] *= 'root'|['user'] = \$_SESSION[login]|" | \
    sed "s|\['password'\] *= ''|['password'] = \$_SESSION[passwd]|" | \
    sed "s|\['only_db'\] *= ''|['only_db'] = \$_SESSION[name]|" > \
    ravencore/var/apps/phpmyadmin/config.inc.php
