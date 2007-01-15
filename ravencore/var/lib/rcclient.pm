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

# package rcclient
#
# The method (coded in perl) to connect to the ravencore socket and submit queries and read data
#

package rcclient;

use Socket;
use MIME::Base64;

use rcfilefunctions;
use serialize;

# connect to the ravencore socket

sub new
{

# inherit the classname from the above package statement, and tell us where the RC_ROOT is

    my ($class, $RC_ROOT) = @_;

# initlize some of our variables

    my $self = {
	ETX => chr(3), # end of text
	EOT => chr(4), # end of transmission
	NAK => chr(21), # end of transmission
        ETB => chr(23), # end of trans. block
        CAN => chr(24), # cancel
	RC_ROOT => $RC_ROOT,
	num_rows => undef,
	insert_id => undef,
	rows_affected => undef,
	alive => undef,
	};
    
# bind the class name to this object and return the results
    
    bless $self, $class;
    
# make sure we are root
    $self->die_error("Must be root") unless $< == 0;

# connect to the socket here. if failed, die with error
    
    socket($self->{socket}, PF_UNIX, SOCK_STREAM, 0) || $self->die_error("Unable to create socket: " . $!);
    connect($self->{socket}, sockaddr_un($RC_ROOT . '/var/rc.sock')) || $self->die_error("Unable to connect to socket: " . $!);
    
# turn on autoflush

    select $self->{socket};
    $| = 1;
    select STDOUT;

# authenticate the administrator password for system access

# generate a random session_id
    my $session_id = $self->gen_random_id(32);

# define our authentication file that will be looked for when we run auth_system
# TODO: repackage the file_ functions in ravencore.pm to their own module and use the file_touch here
    system('touch ' . $self->{RC_ROOT} . '/var/tmp/sessions/SYSTEM_' . $session_id);

# normal auth would look something like this
#    my $resp = $self->do_raw_query('auth ' . $session_id . ' ' . $ipaddress . ' ' . $username . ' ' . $password);
# TODO: create a user-level shell API using this method

    my $resp = $self->do_raw_query('auth_system ' . $session_id . ' ' . $self->get_passwd);
    
# if we got an authentication failure on the socket for some reason, die with the given error

    if( $resp ne "1" )
    {
	print STDERR $resp . "\n";
	exit 1;
    }

    return $self;
}

# generate a random string of $x length
# TODO: work on this a bit. it isn't a "truely random" string generator, but it works good enough for now
# TODO: this is copied from ravencore.pm. put it in a seperate module on its own so both can use a single one

sub gen_random_id
{
    my ($self, $x) = @_;

    my $str;

# $x item long string with randomly generated letters ( from a to Z ) and numbers
    for(my $i=0; $i < $x; $i++)
    {
        my $c = pack("C",int(rand(26))+65);

# 1/3rd chance that this will be a random digit instead
        $c = int(rand(10)) if int(rand(3)) == 2;

        $str .= (int(rand(3))==0?$c:lc($c));
    }

    return $str;

} # end sub gen_random_id

#

sub get_passwd
{
    my ($self) = @_;

    my $password = file_get_contents($self->{RC_ROOT} . '/.shadow');
    chomp $password;

    return $password;
}

# submit a query to the socket

sub do_raw_query
{

    my ($self, $query) = @_;

    my $c;
    my $ret;
    my $data;

# write to the socket

    print { $self->{socket} } encode_base64($query) . $self->{EOT};

# read a byte at a time from the socket, until we get an EOT

    do {

        $data .= $c;

        $ret = read $self->{socket}, $c, 1;

# if $ret is zero, the connection closed on us
        return $self->die_error($data) if ! defined $ret;

    } while ( $c ne $self->{EOT} );

# check for error on the socket ... error starts with NAK byte and ends with ETB

    if($data =~ m/^$self->{NAK}.*$self->{ETB}/)
    {
	my $error = $data;

	$error =~ s/^$self->{NAK}(.*)$self->{ETB}.*$/$1/;
	$data =~ s/^$self->{NAK}.*$self->{ETB}(.*)$/$1/;

	$self->do_error($error);
    }

    return unserialize(decode_base64($data));

}

#

sub do_error
{
    my ($self, $msg) = @_;

    $msg =~ s/^$self->{NAK}//;

    print STDERR $msg . "\n";

}

# call do_error and exit

sub die_error
{
    my ($self, $msg) = @_;

    $self->do_error($msg);

    exit(1);
}

# mysql query equiv
  
sub data_query
{

    my ($self, $sql) = @_;

    my @dat;

# query the socket and get the data based on our question

    my $data = $self->do_raw_query('sql ' . $sql);
#    print $data . "\n";
# now we want to parse this raw data and load our array with it's peices
# we got rows, and columns.... end of row will always be two ETX bytes

    my @rows = split/$self->{ETX}$self->{ETX}/, $data;

# the last element in this first array will always be blank, so remove it
    
#    pop @rows;

# the first two elements in the array are special values
# 1) insert_id , if any
    
    $self->{insert_id} = shift @rows;
    
# 2) rows_affected , if any

    $self->{rows_affected} = shift @rows;

# set our row count to zero
    
    $self->{num_rows} = 0;

# initlize our array... because if we have no data, we need the return value still
# specified as the "array" variable type.

# walk down the rows, and split the column data into it's key => value pair

    foreach my $row_data (@rows)
      {
	
# columns seperated by the ETX byte
	
	  my @item = split/$self->{ETX}/, $row_data;

# we don't do an array_pop here, because the last NUL was removed by the first explode
# where the end-of-row one and the end-of-column ones were joined, which is why we split on two
# the end of the string here is an actual value to consider in the array
	
	  my $i = $self->{num_rows};
	  
# walk down the raw column data, as we still have yet to split into key / val
	
	  foreach my $item_data (@item)
	  {
	    
# data is returned in the following format:
# key{value} ( value is base64 encoded )
# so the two below regex rules parse out the key / val appropriatly
	      
	      my $key;
	      my $val;

	      $key = $val = $item_data;
	      
	      $key =~ s|^(.*)\{.*\}$|\1|s;
	      $val =~ s|^.*\{(.*)\}$|\1|s;

# add this has key => val hash to our dat array

	      $dat[$i]{$key} = decode_base64($val);

#	      print $key . " => " . $dat[$i]{$key} . "\n";
	    
	  } # end foreach
	  
# increment the row number
	
	  $self->{num_rows}++;
	  
      } # end foreach

# return the nested hash

    return @dat;

} # end sub data_query

#

sub service_running
{

    my ($self, $service) = @_;

    return $self->do_raw_query('service_running ' . $service);

}

# a function to change the current database in use

sub use_database
{
    my ($self, $database) = @_;

    return $self->do_raw_query('use ' . $database);
}

# a function to change the admin password. returns true on success, false on failure
# this only checks if the $old password is correct. it's up to the code that calls this to verify
# things like password strength, length, etc.

sub passwd
{
    my ($self, $old, $new) = @_;
    
    return $self->do_raw_query('passwd ' . $old . ' ' . $new);
}

# shift off and return the hash of the current data query. return undef otherwise
# NOTE: when you pass the array here, you need to pass it as a refferent... for example,
# @result = $db->data_query($sql); $row = $db->data_fetch_row(\@result);

sub data_fetch_row { my ($self, $ptr) = @_; return shift @$ptr; }

# return the number of rows of the last data query

sub data_num_rows { my ($self) = @_; return $self->{num_rows} }

# return the insert id of the last data query, 0 if none

sub data_insert_id { my ($self) = @_; return $self->{insert_id} }

# return the number of rows affected by the last query (update, delete). 0 if none

sub data_rows_affected { my ($self) = @_; return $self->{rows_affected} }

# oh no!! die with an error message!! :)

sub die_error
{
    my ($self, $msg) = @_;

    print $msg . "\n";

    exit(1);
} # end sub die_error

#

1;
