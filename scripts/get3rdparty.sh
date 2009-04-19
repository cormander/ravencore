#!/bin/bash

URL=$1
PROG=$(basename $1)

# require both variables
if [ -z "$URL" ] || [ -z "$PROG" ]; then
	echo "Usage: $0 URL"
	exit 1
fi

# check md5sum
md5sum -c src/$PROG.md5

# failed, or file not found
if [ $? -ne 0 ]; then

	if [ ! -x "/usr/bin/curl" ]; then
		echo "Unable to fetch $PROG because curl is not installed"
		exit 1
	fi

	# search local RPM sources first, then the given vendor URL, then the ravencore
	# download site. As soon as a valid download is found, exit
	for i in "file://$(rpm --eval '%{_sourcedir}')/$PROG" "$URL" "http://download.ravencore.com/ravencore/3rdparty/$PROG"; do
		echo "Trying to fetch from $i"
		curl -L $i -o src/$PROG

		md5sum -c src/$PROG.md5

		# passed the test, don't need to look further
		if [ $? -eq 0 ]; then
			exit 0
		fi
	done

	echo "No more URLs to try for $PROG"
	exit 1

fi

