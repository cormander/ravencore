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

use strict;

#
# package SEM
#
# This is a fairly simple object used for the purpose of locking the given file with semaphores, so when other
# processes come along that want to do something with the file, they have to wait in line to operate on it. Note
# that this locking will only work against other processes that use this same locking method - don't expect the
# files locked with this method to be totally safe from anything on the system that may want to write to it, outside
# the scope of ravencore. Improper locking of files often causes bad surprises while reading/writing to a file, so
# if you need to operate on a file that's locked by ravencore, you should either copy or include this package
# in your own code so that it'll play nice.
#

package SEM;

use File::Basename; # functions to make parsing a file to form our semaphore a lot easier
use Fcntl ':flock'; # import LOCK_ constants

# lock the given file via a semaphore. we use this as the object constructor instead of "new"

sub lock {
    my ($class, $file) = @_;

# parse the file path to create our semaphore - our schema is (directory)/.(filename).sem
    $file = dirname($file) . '/.' . basename($file) . '.sem';

# TODO: if set in debug mode, log creation and removal of semaphore files. it is, after all, possible that someone
# kills a process before it has a chance to unlock a file, and any other process trying to access that file will
# hang indefinatly. A debug mode will be able to tell you which file this is.

# do some security checking on the $file, basically, can't go "down" a directory, and must only have characters
# that are allowed in file paths
# TODO: move these security checks into the ravencore object as a function call and call it in every function that
# deals with files and executing system commands
    return 0 unless $file =~ m|^[:a-zA-Z0-9_\-\./]*$|;
    return 0 if $file =~ m|/\.\./|;
    return 0 if $file =~ m|^\.\./|;

# open the semaphore, using perl's lexical filehandles in the open function that were introduced in perl v5.6
    open my $fh, ">" . $file or return 0;

# request an exclusive lock on the semaphore.
# if there already is an outstanding lock on it, this process will wait here until the lock is released
    flock $fh, LOCK_EX;

# TODO: if we're having file locking problems even with our semaphores, we might want to consider doing something
# along the lines of: unless (flock $fh, LOCK_EX | LOCK_NB) { # wait until the file goes bye-bye, recreate it,
# and then continue. This will probably have to be done in a while loop

# set / bless in our local variables so we can later unlock them
    my $self = {
        'handle' => $fh,
        'name' => $file,
    };

    bless $self, $class;

    return $self;
} # end sub lock

# unlock the file's semaphore, close the semaphore, and delete the semaphore

sub unlock {
    my ($self) = @_;

# the first two functions seem to already be accomplished by the 3rd one, the call to unlink, which is why they are
# commented out. Doing all 3 at once with a single call seems safer to me - if for some reason a process is able to
# open up and get a lock on the semaphore before step 3 here occurs, it'll be linked to a non-existant file, and
# locking will be broken. So by just calling unlink here, that will never occur, and hopefully this will do this same
# thing on all systems that support flock

#    flock $self->{'handle'}, LOCK_UN;
#    close $self->{'handle'};
    unlink $self->{'name'};

} # end sub unlock

1; # end package SEM
