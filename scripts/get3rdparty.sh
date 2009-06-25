#!/bin/bash

URL=$1

# require both variables
if [ -z "$URL" ]; then
	echo "Usage: $0 URL"
	exit 1
fi

PROG=$(basename $1)

# check md5sum
md5sum -c src/$PROG.md5 2> /dev/null

# failed, or file not found
if [ $? -ne 0 ]; then

	echo "Need to fetch $PROG ..."

	if [ ! -x "/usr/bin/curl" ]; then
		echo "Unable to fetch $PROG because curl is not installed"
		exit 1
	fi

	# search local RPM sources first, then the given vendor URL, then the ravencore
	# download site. As soon as a valid download is found, exit
	for i in "file://$(rpm --eval '%{_sourcedir}' 2> /dev/null)/$PROG" "$URL" "http://download.ravencore.com/ravencore/3rdparty/$PROG"; do
		echo "Trying to fetch from $i"
		curl -L "$i" -o src/$PROG

		md5sum -c src/$PROG.md5

		# passed the test, don't need to look further
		if [ $? -eq 0 ]; then
			echo "Successfully fetched $PROG"
			exit 0
		fi
	done

	echo "No more URLs to try for $PROG"
	exit 1

fi

