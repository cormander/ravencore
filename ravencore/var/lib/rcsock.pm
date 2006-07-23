
#

package rcsock;

use Socket;
use MIME::Base64;

#

sub new
{

# inherit the classname from the above package statement, and tell us where the socket for this object is

    my ($class, $socket) = @_;

# connect to the socket here. if failed, die with error

    socket(SOCK, PF_UNIX, SOCK_STREAM, 0) || die "socket: $!";
    connect(SOCK, sockaddr_un($socket)) || die "connect: $!";

# turn on autoflush

    select SOCK;
    $| = 1;
    select STDOUT;

# initlize some of our variables

    my $self = {
	ETX => chr(3),
	EOT => chr(4),
	NAK => chr(21),
	num_rows => undef,
	insert_id => undef,
	rows_affected => undef,
	alive => undef,
	socket => SOCK,
	};

# bind the class name to this object and return the results

    bless $self, $class;

    return $self;

}

# submit a query to the socket

sub do_raw_query
{

    my ($self, $query) = @_;

# write to the socket

    print { $self->{socket} } encode_base64($query) . $self->{EOT};

# read a byte at a time from the socket, until we get an EOT

    do {

        $data .= $c;

        $ret = read $self->{socket}, $c, 1;

        if ( $ret == 0 ) { return 0; }
	
    } while ( $c ne $self->{EOT} );
      
# check for error on the socket ... error starts with NAK byte
# return false on error

    if($data =~ m/^$self->{NAK}/)
    {
	$data =~ s/^$self->{NAK}//;

	print $data;

	return 0;
    }

    return $data;

}

# make "true" become true and everything else false

sub str_to_bool
{

    my ($self, $str) = @_;
    
# TODO: remove any whitespace padding

    if($str eq "true") { return 1 } else { return 0 }

}
    
# mysql query equiv
  
sub data_query
{

    my ($self, $sql) = @_;

# ask if we have a database connection.... if not, don't bother trying the data_query
    if ( ! $self->data_alive() ) { return 0 }
    
# query the socket and get the data based on our question

    $data = $self->do_raw_query($sql);

# now we want to parse this raw data and load our array with it's peices
# we got rows, and columns.... end of row will always be two ETX bytes

    @rows = split/$self->{ETX}$self->{ETX}/, $data;

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

    foreach $row_data (@rows)
      {
	
# columns seperated by the ETX byte
	
	  @item = split/$self->{ETX}/, $row_data;

# we don't do an array_pop here, because the last NUL was removed by the first explode
# where the end-of-row one and the end-of-column ones were joined, which is why we split on two
# the end of the string here is an actual value to consider in the array
	
	  $i = $self->{num_rows};
	  
# walk down the raw column data, as we still have yet to split into key / val
	
	  foreach $item_data (@item)
	  {
	    
# replace return characters with : characters, so our regular expressions below won't break;

	      $item_data =~ s/\n/:/g;

# data is returned in the following format:
# key{value} ( value is base64 encoded )
# so the two below regex rules parse out the key / val appropriatly
	      
	      $key = $val = $item_data;
	      
	      $key =~ s|^(.*)\{.*\}$|\1|;
	      $val =~ s|^.*\{(.*)\}$|\1|;

# return the : characters back to newline, and decode the base64 of $val to get the real value

	      $val =~ s/:/\n/g;

# add this has key => val hash to our dat array

	      $dat[$i]{$key} = decode_base64($val);
	    
	  } # end foreach
	  
# increment the row number
	
	  $self->{num_rows}++;
	  
      } # end foreach

# return the nested hash

    return @dat;

} # end sub data_query

# a function to run the given command as root, the file must be in $RC_ROOT/bin and must contain
# special file permissions and ownership to run. this basically replaces the wrapper function.
# output returned from this doesn't nessisarily mean there was an error, we might have wanted to
# have data. so it's up to the code that calls this fuction to decide what to do with output, if any

sub run_cmd
{

    my ($self, $cmd) = @_;

# TODO: add some checking on $cmd here. we do this in the perl server socket code as well, but it
# doesn't hurt to check at each layer

    return decode_base64($self->do_raw_query('run ' . $cmd));

}

# authenticate the administrator password, returns true on success and false on failure

sub data_auth
{
    my ($self, $passwd) = @_;
    
    return $self->str_to_bool($self->do_raw_query('auth ' . $passwd));
}

# a function to change the current database in use

sub use_database
{
    my ($self, $database) = @_;

    return $self->str_to_bool($self->do_raw_query('use ' . $database));
}

# a function to change the admin password. returns true on success, false on failure
# this only checks if the $old password is correct. it's up to the code that calls this to verify
# things like password strength, length, etc.

sub change_passwd
{

    my ($self, $old, $new) = @_;
    
    return $self->str_to_bool($self->do_raw_query('passwd ' . $old . ' ' . $new));
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

# tell us if we have a database connection. the results of this function are cached,
# so we don't keep asking the socket for every single query. If the connection dies in the
# middle of a page load, an error will be issued via the do_raw_query call

sub data_alive
{

    my ($self) = @_;

# if DATA_QUERY_SHELL is in the enviroment, return true

    if($ENV{DATA_QUERY_SHELL}) { return 1 }

# if $alive is already set, don't bother asking again

    if( ! $self->{alive} )
      {
	$self->{alive} = $self->str_to_bool($self->do_raw_query('connect'));
      }
    
    return $self->{alive};
    
}

#

1;
