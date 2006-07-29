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

    open PASSWD, $CONF{'RC_ROOT'} . "/.shadow";

    my $passwd = <PASSWD>;
    chomp $passwd;
    
    close PASSWD;

    return $passwd;

}

# read a configuration file. files are always in format: NAME=VALUE

sub read_conf_file
{

    my $conf = shift;

    open CONF, $conf
        or die "Unable to open configuration file: $conf\n";

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

