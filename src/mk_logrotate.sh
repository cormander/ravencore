#!/bin/bash

# our argument on the command line is the ravencore root

cat <<EOF
$1/var/log/*log {

    create 644 root root
    size 1000M
    compress
    rotate 5
    nomail
    postrotate
        /etc/init.d/ravencore restart
    endscript

}
EOF
