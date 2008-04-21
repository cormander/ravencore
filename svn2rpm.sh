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

v_tree=0.3.x
v=0.3.3

RPM_SOURCES=/usr/src/redhat/SOURCES

# simple check to make sure we're in the right directory....

if [ ! -f GPL ] || [ ! -f LICENSE ] || [ ! -f Makefile ] || [ ! -f README.install ] || [ ! -d  src ] || [ ! -d .svn ] || [ ! -f svn2rpm.sh ]; then
	echo "Don't appear to be in the subversion directory..."
	exit 1
fi

# go down one and make sure we are where we expect

cd ..

if [ ! -d $v_tree ]; then
	echo "WTF! The subversion directory isn't named $v_tree..."
	exit 1
fi

mv $v_tree ravencore-$v
tar --exclude ".svn" -czpf $RPM_SOURCES/ravencore-$v.tar.gz ravencore-$v
mv ravencore-$v $v_tree

cd $v_tree

# build an RPM out of ravencore

rpmbuild -ba src/ravencore.spec

if [ $? -eq 0 ]; then

	# remove ravencore from the system
	rpm -e ravencore
	rm -rf /usr/local/ravencore

	# install what we just built
	rpm -Uvh /usr/src/redhat/RPMS/noarch/ravencore-$v-1.noarch.rpm

	# restart!!!
	/etc/init.d/ravencore restart

fi

