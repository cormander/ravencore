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

$ENV{'RC_ETC'} = ( $ENV{'RC_ETC'} ? $ENV{'RC_ETC'} : '/etc/ravencore.conf' );

# make sure we are root

die "Must be root" unless $< == 0;

# get our base configuration file

&read_conf_file($ENV{'RC_ETC'});

#
# TODO: make sure we have certian variables here... we probably only need to check for RC_ROOT, but
# die with error if we don't have it, or if it doesn't exist on the filesystem
#

#
# TODO: write a subroutine for reading the database conf, and call it here
#

# now that we know where our root directory is, get our database configuration file

&read_conf_file($CONF{'RC_ROOT'} . "/database.cfg");

#
# TODO: write a subroutine for reading the database conf, and call it here
#

# destroy the given value. must pass it as a reference, ex: &destroy(\$value);

sub destroy
{
    my $val = shift;

    undef $val;

}

# a function use to read the password out of the .shadow file

sub get_passwd
{

    open PASSWD, $CONF{'RC_ROOT'} . "/.shadow" or &die_error("Unable to open file: $!");

    my $passwd = <PASSWD>;
    chomp $passwd;
    
    close PASSWD;

    return $passwd;

}

# read a configuration file. files are always in format: NAME=VALUE

sub read_conf_file
{

    my $file = shift;

    open CONF, $file or &die_error("Unable to open file $file: $!");

    while(<CONF>)
    {
        chomp $_;

        if($_ =~ m/^[A-Z0-9_]*=/)
        {
            my $key = my $val = $_;

            $key =~ s/^([A-Z0-9_]*)=.*/\1/;
            $val =~ s/^([A-Z0-9_]*)=//;

# remove starting and ending quotations

            $val =~ s/^("|')//;
            $val =~ s/('|")$//;

            $CONF{$key} = $val;

        }

    }

    close CONF;

} # end sub read_conf_file

#

sub read_db_conf
{
    
    my $sql = "select * from settings";
    
    my @result;
    my $key;
    my $row;

    @result = $db->data_query($sql);

    while( $row = $db->data_fetch_row(\@result) )
    {
	$key = $row->{'setting'};
	$CONF{$key} = $row->{'value'};
    }

}

#
# Functions that act like certian bash commands or php functions
#

# create a file if it doesn't exist

sub touch
{

    my @files = @_;

    foreach $file (@files)
    {
	if( ! -f $file )
	{
	    open FILE, ">$file" or &die_error("Unable to open file $file: $!");
	    close FILE;
	}
    }
}

# return the contents of a file as a string

sub file_get_contents
{
    my $file = shift;

    my $contents;

    open FILE, $file or &die_error("Unable to open file $file: $!");
    my @content = <FILE>;
    close FILE;

    foreach (@content) { $contents .= $_ }

    return $contents;

}

# write a string to a file. first argument is the filename, second is a string containing the contents

sub file_write
{

    my $file = shift;
    my $contents = shift;

    open FILE, ">" . $file or &die_error("Unable to open file $file: $!");
    print FILE $contents;
    close FILE;

}

# append to a file, rather then overwrite it

sub file_append
{

    my $file = shift;
    my $contents = shift;

    open FILE, ">>" . $file or &die_error("Unable to open file $file: $!");
    print FILE $contents;
    close FILE;

}

# a function to... well, delete a file!
# TODO: finish this

sub delete_file
{

    my $file = shift;

}

# a function to do the equiv of a "mkdir -p"
# TODO: Finish this, and use it everywhere instead of perl's "mkdir"

sub mkdir_p
{
    my @dirs = @_;

# walk down our list of directories to create

    foreach my $dir (@dirs)
    {

	my $full_dir;

# split the directory into its parts

	my @parts = split/\//, $dir;

# first one will always be /
	shift @parts;

	foreach $part (@parts)
	{
# append this part to the previous to make the full path thus far, and create it
	    $full_dir .= "/" . $part;
	    mkdir $full_dir unless -d $full_dir;
	}

    }

}

# perl's built-in chown requires the UID and GID. This is a wrapper to change the name into their ID
# values and call chown

sub chown_file
{

    my $str = shift;
    my @files = @_;

    my $user;
    my $group;

# the $str can be in format "user:group", and if not, $group will be empty    
    ($user, $group) = split /:/, $str;

# chown the file with the user. if $group doesn't exist, use -1 to leave the group unchanged
    chown getpwnam($user), ( $group ? getgrnam($group) : -1 ), @files;

}

#

sub die_error
{
    my $msg = shift;

    print $msg . "\n";

    exit(1);
}
