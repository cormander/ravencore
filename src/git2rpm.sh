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
v=0.4.0

RPM_DIR=$(rpm --eval '%{_rpmdir}' 2> /dev/null)
RPM_SOURCES=$(rpm --eval '%{_sourcedir}' 2> /dev/null)

reinstall=$1

# simple check to make sure we're in the right directory....
git log &> /dev/null

if [ $? -ne 0 ]; then
	echo "Don't appear to be in the git directory..."
	exit 1
fi

# check to make sure those RPM directories exist

if [ ! -d "$RPM_DIR" ] || [ ! -d "$RPM_SOURCES" ]; then
	echo "Please configure your RPM build tree correctly."
	exit 1
fi

check_packages="make rpm-build unzip patch curl which git"

rpm -q $check_packages &> /dev/null

if [ $? -ne 0 ] && [ ! -d "/cygdrive/c" ]; then

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

# now check to make sure the actual commands exist

for pkg in $check_packages; do

	cmd=$(echo $pkg | sed 's/\-//g')

	which $cmd &> /dev/null

	if [ $? -ne 0 ]; then
		echo "Unable to find $cmd"
		exit 1
	fi

done

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

# save 3rd party files to $RPM_SOURCES
for i in $(ls src/*.md5); do
	file=$(echo $i | sed 's/\.md5$//')
	[ -f $file ] && mv -f $file $RPM_SOURCES
done

# if the current working tree doesn't match the index, prompt the user
# do this even if we have no tty, because if the build tree was manually altered
# we would want the build to fail to let us know that

git_changes_work=$(git diff 2> /dev/null | wc -l)
git_changes_index=$(git diff --cached 2> /dev/null | wc -l)

# add the two wc together
git_changes=$(expr "$git_changes_work" "+" "$git_changes_index")

if [ $git_changes -gt 0 ]; then

	echo "***********"
	echo "** ERROR **"
	echo "***********"
	echo "You have differences between your working tree and/or index from the current HEAD."
	echo "Please commit them before building."

	exit 1

fi

# was this a bare build?
BARE_BUILD=0
[ -f bare.info ] && BARE_BUILD=1

# make distclean after the above check to make it more obvious the things
# that need to exist in the .gitignore file

make distclean

# fetch the current branch and archive it to a tar.gz file

BRANCH=$(git branch | grep '^\*' | awk '{print $2}')

# make sure the tty command exists

if [ ! -x "$(which tty 2> /dev/null)" ]; then
	echo "WTF? You don't have the tty command? Fail..."
	exit 1
fi

# if we don't have a tty, we're probably being built in hudson - skip the branch check

tty &> /dev/null

no_tty=$?

# if the branch isn't "master", prompt to continue

if [ $no_tty -eq 0 ] && [ "$BRANCH" != "master" ]; then

	echo "************"
	echo "** NOTICE **"
	echo "************"
	echo -n "You are not on branch master. Are you sure you want to build the RPM on branch $BRANCH? y/n: "

	read answer

	if [ "$answer" != "y" ]; then
		echo "Exiting."
		exit 1
	fi

fi

# force branch to be master when no tty

if [ $no_tty -ne 0 ]; then
	BRANCH="master"
fi

echo "*** Building RPM using git branch $BRANCH ***"

git archive --format=tar --prefix="ravencore-$v/" $BRANCH | gzip -9 > "$RPM_SOURCES/ravencore-$v.tar.gz"

if [ $? -ne 0 ]; then
	echo "The git-archive command failed"
	exit 1
fi

if [ "$BARE_BUILD" = 0 ]; then
	RPM_ARGS=""
else
	RPM_ARGS="--with bare"
fi

# append -bb to RPM_ARGS
RPM_ARGS="$RPM_ARGS -bb"

# build an RPM out of ravencore
if [ -n "$DO_RELEASE" ]; then
	echo "Doing release quality build"
	rpmbuild $RPM_ARGS --with release src/ravencore.spec
else
	echo "Doing snapshot build"
	rpmbuild $RPM_ARGS src/ravencore.spec
fi

buildret=$?

if [ $buildret -eq 0 ] && [ -n "$reinstall" ]; then

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
		
		rpm -Uvh $RPM_DIR/noarch/ravencore-$v-1.noarch.rpm
		/etc/init.d/ravencore restart

	else

		# find what we just built
		therpm=$(ls $RPM_DIR/noarch/ravencore-$v-0.*.noarch.rpm 2> /dev/null | sort | tail -n 1)

		if [ -f $therpm ]; then
			echo "Found: $therpm"
			rpm -Uvh $therpm
			/etc/init.d/ravencore restart
		else
			echo "Successful build of RPM, but couldn't find where it ended up"
		fi

	fi

fi

exit $buildret

