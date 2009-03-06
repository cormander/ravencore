#!/bin/bash

cat ravencore/var/apps/phpmyadmin/libraries/config.default.php | \
    sed "s/= 'config';/= 'http';/" > \
    ravencore/var/apps/phpmyadmin/config.inc.php

