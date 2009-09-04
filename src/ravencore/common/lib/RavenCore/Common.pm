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

#
# file functions that ravencore uses all over the place
#

package RavenCore::Common;

use strict;
use warnings;

use Digest::SHA::PurePerl qw(sha1_hex);
use SEM;

use vars qw(@ISA @EXPORT);
@ISA     = qw(Exporter);
@EXPORT  = qw(file_get_contents file_touch file_move file_get_array file_write file_append file_move file_delete file_copy file_chown file_chown_r file_chmod_r file_diff mkdir_p dir_list find_in_path in_array pidof is_ip gen_random_id make_passwd_hash verify_passwd_by_hash is_email is_ok_password _ );

# constants
use constant SALT_LENGTH => 24;

#
# File function calls... read/write/append/delete/move/etc, with locking support
#

# create a file if it doesn't exist

sub file_touch {
	my (@files) = @_;

	foreach my $file (@files)
	{
		if ( ! -f $file )
		{
			open FILE, ">" . $file or die("Unable to open file $file: $!");
			close FILE;
		}
	}

}

# move a file

sub file_move {
	my ($file, $dest) = @_;

	# lock our file and destination files
	my $sem = SEM->lock($file);
	my $sem2 = SEM->lock($dest);

	# clobber the destination file, if it exists
	unlink $dest;

	# issue the move command
	rename($file,$dest);

	# unlock the files
	$sem->unlock;
	$sem2->unlock;
}

# return the contents of a file as a string

sub file_get_contents {
	my ($file, $nolock) = @_;

	my $contents;

	# TODO: make sure this is what we really want to be doing
	# if the file doesn't exist, simply return an empty string
	# TODO: issue debug and/or error message here too?
	if ( ! -f $file || ! $file )
	{
		return "";
	}

	# lock the file, unless otherwise told not to
	my $sem = SEM->lock($file) unless $nolock;

	# read in the file
	open FILE, $file or die("Unable to open file $file: $!");
	my @content = <FILE>;
	close FILE;

	# unlock the file
	$sem->unlock unless $nolock;

	# turn the array into a string
	foreach (@content) { $contents .= $_ }

	# return the contents
	return $contents;
} # end sub file_get_contents

# return the contents of a file in an array, each line is an array element

sub file_get_array {
	my ($file, $nolock) = @_;

	my $contents;

	# make a call to file_get_contents to do the dirty work for us.. call it based on whether or not we got the
	# $nolock variable
	if ($nolock)
	{
		$contents = file_get_contents($file, $nolock);
	}
	else
	{
		$contents = file_get_contents($file);
	}

	# each line of the file is an element in the array returned
	return split /\n/, $contents;
}

# write a string to a file. first argument is the filename, second is a string containing the contents

sub file_write {
	my ($file, $contents) = @_;

	$contents = "" unless $contents;

	# lock our file for writing
	my $sem = SEM->lock($file);

	open FILE, ">" . $file or die ("Unable to open file $file: $!");
	print FILE $contents;
	close FILE;

	$sem->unlock;
}

# append to a file, rather then overwrite it

sub file_append {
	my ($file, $contents) = @_;

	# lock the file
	my $sem = SEM->lock($file);

	# open the file for appending
	open FILE, ">>" . $file or die ("Unable to open file $file: $!");
	print FILE $contents;
	close FILE;

	$sem->unlock;
}

# copy one file to another

sub file_copy {
	my ($file, $dest) = @_;

	file_write($dest, file_get_contents($file));
}

# compare one file to another, tell us if they are different at all

sub file_diff {
	my ($file, $file2) = @_;

	return 1 if file_get_contents($file) ne file_get_contents($file2);
	return 0;
}

# a function to... well, delete a file!

sub file_delete {
	my ($file, $nolock) = @_;

	# get a lock on the file... we don't want to destroy the file while someone is in it!
	my $sem = SEM->lock($file) unless $nolock;

	# remove the file
	unlink $file;

	$sem->unlock unless $nolock;
}

# perl's built-in chown requires the UID and GID. This is a wrapper to change the name into their ID
# values and call chown

sub file_chown {
	my ($str, @files) = @_;

	my $user;
	my $group;

	# the $str can be in format "user:group", and if not, $group will be empty
	($user, $group) = split /:/, $str;

	# chown the file with the user. if $group doesn't exist, use -1 to leave the group unchanged#
	# TODO: fix this, for some reason it isn't working

	system ("chown " . $str . " @files 2> /dev/null");

}

# recursive file_chown

sub file_chown_r {
	my ($str, @files) = @_;

	# declair local array of all files to chown
	my @sub_files;

	# TODO: the below is commented out because it's too friggen slow... make it faster

	# do a recursive find on each file/dir in the list and add it to the local array
	#    foreach my $file (@files)
	#    {
	#       find(sub { unshift @sub_files, $File::Find::name; }, $file);
	#    }

	# chown all the files
	#    file_chown($str, @sub_files);

	# just do a system call for now
	system ("chown -R " . $str . " @files 2> /dev/null");
}

# recursive for perl's chmod function

sub file_chmod_r {
	my ($mode, @files) = @_;

	# declair local array of all files to chown
	#    my @sub_files;

	# TODO: the below is too slow... make it faster

	#    foreach my $file (@files)
	#    {
	#       find(sub { unshift @sub_files, $File::Find::name }, $file);
	#    }

	# chmod all the files
	#    chmod $mode, @sub_files;

	system ("chmod -R " . $mode . " @files 2> /dev/null");
}

# TODO: change mkdir_p to dir_create ??

# a function to do the equiv of a "mkdir -p"

sub mkdir_p {
	my (@dirs) = @_;

	# walk down our list of directories to create
	foreach my $dir (@dirs)
	{

		my $full_dir;

		# split the directory into its parts
		my @parts = split/\//, $dir;

		# first one will always be /
		shift @parts;

		foreach my $part (@parts)
		{
			# append this part to the previous to make the full path thus far, and create it
			$full_dir .= "/" . $part;
			mkdir $full_dir unless -d $full_dir;
		}

	}

}

# returns an array; a directory listing of the given dir

sub dir_list {
	my ($dir) = @_;

	# TODO: do checks on $dir to make sure it's safe

	open DIR, 'ls -1 ' . $dir . '|';

	my @cont = <DIR>;

	close DIR;

	foreach (@cont) { chomp }

	return @cont;

}

# quick duplicate of the PHP in_array() function

sub in_array {
	my $search = shift;
 
	foreach my $val (@_) {
		return 1 if $search eq $val;
	}

	return 0;

}

# look in the PATH enviroment for a file, return the full path if found, nothing otherwise

sub find_in_path {
	my ($file) = @_;

	my @dirs = split /:/, $ENV{PATH};

	foreach my $dir (@dirs) {
		return $dir . '/' . $file if -f $dir . '/' . $file;
		return $dir . '/' . $file . '.exe' if -f $dir . '/' . $file . '.exe';
	}

	return;
}

# returns an array of pids of the given process name, if any

sub pidof {
	my ($prog) = @_;

	my $pidof = `pidof $prog`;
	chomp $pidof;

	return split / /, $pidof;
}

# verify that the given argument is an IP address

sub is_ip {
	return $_[0] =~ /^(\d{1,3}\.){3}\d{1,3}$/;
}

# generate a random string of $x length
# TODO: work on this a bit. it isn't a "truely random" string generator, but it works good enough for now

sub gen_random_id {
	my ($x) = @_;

	my $str;

	# $x item long string with randomly generated letters ( from a to Z ) and numbers
	for (my $i=0; $i < $x; $i++)
	{
		my $c = pack("C",int(rand(26))+65);

		# 1/3rd chance that this will be a random digit instead
		$c = int(rand(10)) if int(rand(3)) == 2;

		$str .= (int(rand(3))==0?$c:lc($c));
	}

	return $str;

}

#

sub make_passwd_hash {
	my ($passwd) = @_;

	# salt the password and sha1sum it
	my $salt = gen_random_id(SALT_LENGTH);

	return $salt . sha1_hex($salt.$passwd);
}

#

sub verify_passwd_by_hash {
	my ($passwd, $hash) = @_;

	return unless $passwd;
	return unless $hash;

	# retrieve the salt from the hash
	my $salt = substr $hash, 0, SALT_LENGTH;

	# retrieve the sha1sum
	my $sha1sum = substr $hash, SALT_LENGTH;

	return 1 if $sha1sum eq sha1_hex($salt.$passwd);
}

# email address regular expression

sub is_email {
	my ($email) = @_;

	return 1 if $email =~ /^([a-zA-Z\d]+((\.||\-||_)[a-zA-Z\d]?)?)*[a-zA-Z\d]@([a-zA-Z\d]+((\.||\-)[a-zA-Z\d]?)?)*[a-zA-Z\d]\.[a-zA-Z]+$/;

	return 0;
}

# central place to check if a given password is OK to use

sub is_ok_password {
	my ($passwd) = @_;

	return (1, "You must enter a password") unless $passwd;

	my $error;
	my $msg;

	if (length($passwd) < 5) {
		$msg = "Your password must be at least 5 characters long.";
		$error = 1;
	}

	if ($passwd !~ /\d/) {
		$msg = "Your password must contain at least one digit.";
		$error = 1;
	}

	if ($passwd !~ /[a-zA-Z]/) {
		$msg = "Your password must contain at least one alphabetical character.";
		$error = 1;
	}

	return ($error, $msg);
}

# an alias for the gettext function

sub _ {
	my $str = shift;
	$str = gettext($str) if $INC{'Locale/gettext.pm'};
	return sprintf($str, @_);
}

1;
