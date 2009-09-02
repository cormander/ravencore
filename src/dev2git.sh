#!/bin/sh
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

. /etc/ravencore.conf || exit 1

if [ -L $RC_ROOT ]; then
	echo "Looks like $RC_ROOT is already a symlink."
	exit 1
fi

# simple check to make sure we're in the right directory....
git log &> /dev/null

if [ $? -ne 0 ]; then
        echo "Don't appear to be in the git directory..."
        exit 1
fi

if [ ! -f .gitignore ]; then
	echo "No .gitignore file... how am I supposed to know what to copy?"
	exit 1
fi

# remove files in .gitignore
make distclean

# copy each file, ignoring ones with a wildcard match
for file in $(cat .gitignore | grep -v '*' | sed 's|^ravencore/||'); do
	mv $RC_ROOT/$file ravencore/$file
done

rm -rf $RC_ROOT

# if our directory is a symlink, follow the symlink
if [ -L $(pwd) ]; then
	destdir=$(ls -ld $(pwd) | awk '{print $11}')
else
	destdir=$(pwd)
fi

# link it
ln -s $destdir/ravencore $RC_ROOT

# the socket gets blown aways, so we have to restart
/etc/init.d/ravencore restart

