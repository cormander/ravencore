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
v=0.3.5

RPM_ROOT=/usr/src/redhat

reinstall=$1

# simple check to make sure we're in the right directory....

if [ ! -f GPL ] || [ ! -f LICENSE ] || [ ! -f Makefile ] || [ ! -f README.install ] || [ ! -d  src ] || [ ! -d .git ] || [ ! -f git2rpm.sh ]; then
	echo "Don't appear to be in the git directory..."
	exit 1
fi

check_packages="make rpm-build unzip patch wget"

rpm -q $check_packages &> /dev/null

if [ $? -ne 0 ]; then

	if [ ! -x /usr/bin/yum ]; then
		echo "You're missing some packages to build ravencore, and don't have yum to install them"
		exit 1
	fi

	yum -y install $check_packages

	rpm -q $check_packages &> /dev/null

	if [ $? -ne 0 ]; then
		echo "Attempted to install packages, but failed: $check_packages"
		exit 1
	fi

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

# remove files based on whether this is a release or not
if [ -n "$DO_RELEASE" ]; then
	# remove all .gitignore files (so sources have to be re-downloaded, this
	# keeps the size of the tarball and src.rpm files down
	rm -rf $(cat .gitignore)
else
	# remove files in .gitignore, ignore wildcard matches so we don't have to
	# re-download all the 3rd party source on each build
	rm -rf $(cat .gitignore | grep -v '*')
fi

# go down one and make sure we are where we expect

cd ..

if [ ! -d $mydir ]; then
	echo "WTF! Our current working directory was $mydir but I don't see it now..."
	exit 1
fi

mv $mydir ravencore-$v
tar --exclude ".git" -hczpf $RPM_ROOT/SOURCES/ravencore-$v.tar.gz ravencore-$v
mv ravencore-$v $mydir

cd $mydir

# build an RPM out of ravencore
if [ -n "$DO_RELEASE" ]; then
	rpmbuild -ba --with release src/ravencore.spec
else
	rpmbuild -ba src/ravencore.spec
fi

if [ $? -eq 0 ] && [ -n "$reinstall" ]; then

	# if it's a symlink, just remove the symlink
	if [ -L /usr/local/ravencore ]; then
		rm -f /usr/local/ravencore
	else
		rm -rf /usr/local/ravencore
	fi

	# remove ravencore from the system
	rpm -e ravencore

	# location of RPM depends on how it was built

	if [ -n "$DO_RELEASE" ]; then
		
		rpm -Uvh $RPM_ROOT/RPMS/noarch/ravencore-$v-1.noarch.rpm
		/etc/init.d/ravencore restart

	else

		# find what we just built
		therpm=$(ls $RPM_ROOT/RPMS/noarch/ravencore-$v-0.*.noarch.rpm 2> /dev/null | sort | tail -n 1)

		if [ -f $therpm ]; then
			echo "Found: $therpm"
			rpm -Uvh $therpm
			/etc/init.d/ravencore restart
		else
			echo "Successful build of RPM, but couldn't find where it ended up"
		fi

	fi

fi

