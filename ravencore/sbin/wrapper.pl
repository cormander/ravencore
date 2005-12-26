#!/usr/bin/perl
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

# destroy the enviroment
%ENV = '';

$data = "@ARGV";

if ($data =~ /^([-\@\w. ]+)$/) {
    $data = $1;                     # $data now untainted
} else {
    die "Unsafe command";        # log this somewhere
}

$RC_ROOT = `grep '^RC_ROOT=' /etc/ravencore.conf | sed 's/.*=//'`;

if ($RC_ROOT =~ /^([\/-\w.]+)$/) {
    $RC_ROOT = $1;
} else {
    die "Bad RC_ROOT: $RC_ROOT";
}

# exit out with error if the command to run isn't in the ravencore bin

if( ! -x $RC_ROOT . "/bin/" . $ARGV[0] ) { print "Command doesn't exist\n";exit 1; }

# set our real uid and such to root
$< = $>;
$( = $) = 0;

exec $RC_ROOT . "/bin/" . $data;

