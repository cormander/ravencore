#!/bin/bash
#
#                  RavenCore Hosting Control Panel
#                Copyright (C) 2005  Corey Henderson
#
#     This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#

# version
v=0.3.3

RPM_SOURCES=/usr/src/redhat/SOURCES

reinstall=$1

# simple check to make sure we're in the right directory....

if [ ! -f GPL ] || [ ! -f LICENSE ] || [ ! -f Makefile ] || [ ! -f README.install ] || [ ! -d  src ] || [ ! -d .git ] || [ ! -f git2rpm.sh ]; then
	echo "Don't appear to be in the git directory..."
	exit 1
fi

# check version to make sure we match in Makefile and src/ravencore.spec

makeversion=$(grep '^VERSION=' Makefile | cut -d = -f 2)
specversion=$(grep '^Version: ' src/ravencore.spec | cut -d ' ' -f 2)

if [ $v != $makeversion ]; then
	echo "Makefile VERSION doesn't match ours!"
	exit 1
fi

if [ $v != $specversion ]; then
	echo "RPM spec Version doesn't match ours!"
	exit 1
fi

# remember this directory name
mydir=$(basename $(pwd))

# go down one and make sure we are where we expect

cd ..

if [ ! -d $mydir ]; then
	echo "WTF! Our current working directory was $mydir but I don't see it now..."
	exit 1
fi

mv $mydir ravencore-$v
tar --exclude ".git" -czpf $RPM_SOURCES/ravencore-$v.tar.gz ravencore-$v
mv ravencore-$v $mydir

cd $mydir

# build an RPM out of ravencore

rpmbuild -ba src/ravencore.spec

if [ $? -eq 0 ] && [ -n "$reinstall" ]; then

	# remove ravencore from the system
	rpm -e ravencore
	rm -rf /usr/local/ravencore

	# install what we just built
	rpm -Uvh /usr/src/redhat/RPMS/noarch/ravencore-$v-1.noarch.rpm

	# restart!!!
	/etc/init.d/ravencore restart

fi

