#!/bin/bash

URL=$1
PROG=$(basename $1)

URL_BAK="http://download.ravencore.com/ravencore/3rdparty"

# require both variables
if [ -z "$URL" ] || [ -z "$PROG" ]; then
	echo "Usage: $0 URL"
	exit 1
fi

# fetch if not there (or size is zero)
if [ ! -s src/$PROG ]; then
	wget -O src/$PROG "$URL"
fi

# check md5sum
md5sum -c src/$PROG.md5 &> /dev/null

# if md5sum check fails, get for ravencore.com
if [ $? -ne 0 ]; then
	echo "Problem with MD5sum with $PROG"

	mv -f src/$PROG src/$PROG.broken

	wget -O src/$PROG "$URL_BAK/$PROG"

	# check md5 again, if fails, exit with error
	md5sum -c src/$PROG.md5 &> /dev/null

	if [ $? -ne 0 ]; then
		echo "Unable to find a correct $PROG"
		exit 1
	fi
fi

