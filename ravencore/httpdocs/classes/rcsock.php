<?php
/*
                 RavenCore Hosting Control Panel
               Copyright (C) 2005  Corey Henderson

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/*

class rcsock

This class was coded as the client to the ravencore database socket. It writs SQL statements
( and a few other kinds of things ) to the socket, and reads the output. The purpose here is
so that the PHP pages have free access to the database, without having to know the database
password, so in the event of a ravencore PHP page ( or 3rd party page ) getting exploited,
they don't have permission to access the password.

Only the user running the webserver (rcadmin) is supposed to be able to write to the socket,
handled by the filesystem permissions, so it's pretty safe from that angle. The most anyone
can do this way is add/delete/alter stuff in the database, which they could already do if they
had the password. But this way, they can't read OR change the admin password, which puts an
exploited system in a much safer position.

This class is also coded as a replacement to the wrapper. No matter how much input checking
you do, having a set uid command that is executable from a simple shell is very dangerous.
With this, a hacker would have to gain additional access to the system to be able to talk to
the socket, as there are no common shell commands to do this (that I am aware of, anyway).

*/

class rcsock {

  // open the socket connection when the object is created

  function rcsock($socket)
  {
    
    global $socket_err;

    // set our socket variables...

    $this->ETX = chr(3); // end of text
    $this->EOT = chr(4); // end of transmission
    $this->NAK = chr(21); // negative acknowledge

    list($major, $minor, $release) = explode('.',phpversion());

    if($major == 5) $socket = 'unix://' . $socket;

    // open our socket...

    $this->sock = fsockopen($socket, 0, $errno, $errstr);
    
    // make sure our return value is a rea resource... if not, we shouldn't continue
    
    if(!is_resource($this->sock))
      {

	$socket_err = 'Unable to open socket: ' . $socket . '<br>Error code: ' . $errno . ' - ' . $errstr . '<p>Please restart the control panel';

      }
    
  }

  // submit a query to the socket, and return the raw data that is the answer. This isn't always
  // an SQL query. This function should probably only be used within this object class.
  //   supported commands:
  //     auth <password>
  //     run <command>
  //     use <database>
  //     passwd <old password> <new password>
  //     SQL statement (select, insert, update, delete)
  //     connect ( tell us if we have a db connection )

  function do_raw_query($query)
  {

    // submit the query to the socket, base64 encoded to be binary safe, and the EOT character tells
    // the socket that we're done transmitting this query

    fwrite($this->sock, base64_encode($query) . $this->EOT);

    // flush our writting to the socket so we get an imidate reply on the data
    
    fflush($this->sock);

    // read data until the EOT byte
    // the $data .= $c; comes FIRST so that $data won't have an EOT byte at the end of it,
    // when the string is done being built

    do {
      
      $data .= $c;
      
      $c = fgetc($this->sock);
      
    } while ( $c != $this->EOT );
      
    // check for an error on the data returned. if the first character in $data is a NAK byte,
    // then we know there was trouble.... print the error and exit. We don't have to worry about
    // binary files fudging this up, because just about everything else is returned as a base64
    // encoded string
    
    if( preg_match('|^' . $this->NAK . '|', $data) )
      {
	
	print '<br/>
<table>
<tr>
<td nowrap><b>ERROR on query:</b></td>
<td>' . $query . '</td>
</tr><tr>
<td nowrap><b>Server responded with:</b></td>
<td>' . str_replace($this->NAK,'',$data) . '</td>
</tr>
</table>';
	
	// we don't want to return any data, so return false
	
	return false;

      }
    
    // return the raw response. whichever function calling do_raw_query will parse the data
    // as appropriate
    
    return $data;

  }
  
  // a function to convert a string to it's boolian... "true" becomes true, everything else
  // becomes false

  function str_to_bool($str)
  {
    
    // remove any whitespace padding

    $str = trim($str);

    switch($str)
      {
      case "true":
	return true;
      default:
	return false;
      }
    
  }

  // query the database with $sql statement, and return the results to be retrieved with
  // data_fetch_array(). This returns an actual array of arrays, instead of a resource or
  // a pointer like most other database objects, so keep that in mind.
  
  function data_query($sql)
  {

    // if we don't have a database connection, don't bother doing the query

    if ( ! $this->data_alive() ) return false;
    
    // query the socket and get the data based on our question

    $data = $this->do_raw_query($sql);

    // now we want to parse this raw data and load our array with it's peices
    // we got rows, and columns.... end of row will always be two ETX bytes

    $rows = explode($this->ETX.$this->ETX, $data);

    // the last element in this first array will always be blank, so remove it
    
    array_pop($rows);

    // the first two elements in the array are special values
    // 1) insert_id , if any

    $this->insert_id = array_shift($rows);

    // 2) rows_affected , if any

    $this->rows_affected = array_shift($rows);

    // set our row count to zero
    
    $this->num_rows = 0;
    
    // initlize our array... because if we have no data, we need the return value still
    // specified as the "array" variable type.

    $dat = array();

    // walk down the rows, and split the column data into it's key => value pair
    
    foreach($rows as $row_data)
      {
	
	// columns seperated by the ETX byte
	
	$item = explode($this->ETX, $row_data);

	// we don't do an array_pop here, because the last NUL was removed by the first explode
	// where the end-of-row one and the end-of-column ones were joined, which is why we split on two
	// the end of the string here is an actual value to consider in the array
	
	$i = $this->num_rows;

	// walk down the raw column data, as we still have yet to split into key / val
	
	foreach($item as $item_data)
	  {
	    
	    // replace return characters with : characters, so our regular expressions below won't break;

	    $item_data = str_replace("\n",":",$item_data);

	    // data is returned in the following format:
	    // key{value} ( value is base64 encoded )
	    // so the two below regex rules parse out the key / val appropriatly
 	    
	    $key = preg_replace('|^(.*)\{.*\}$|','\1',$item_data);
	    $val = preg_replace('|^.*\{(.*)\}$|','\1',$item_data);

	    // return the : characters back to newline, and decode the base64 of $val to get the real value

	    $dat[$i][$key] = base64_decode(str_replace(":","\n",$val));
	    
	  } // end foreach($item as $item_data)
	
	// increment the row number
	
	$this->num_rows++;
	
      } // end foreach($rows as $row_data)

    // return the nested array

    return $dat;

  } // end function data_query($sql)

  // a function to run the given command as root, the file must be in $RC_ROOT/bin and must contain
  // special file permissions and ownership to run. this basically replaces the wrapper function.
  // output returned from this doesn't nessisarily mean there was an error, we might have wanted to
  // have data. so it's up to the code that calls this fuction to decide what to do with output, if any

  function run_cmd($cmd)
  {

    // TODO: add some checking on $cmd here. we do this in the perl socket code as well, but it doesn't
    // hurt to check at each layer

    return base64_decode($this->do_raw_query('run ' . $cmd));

  }

  // authenticate the administrator password, returns true on success and false on failure

  function data_auth($passwd)
  {
    return $this->str_to_bool($this->do_raw_query('auth ' . $passwd));
  }

  // a function to change the current database in use

  function use_database($database)
  {
    return $this->str_to_bool($this->do_raw_query('use ' . $database));
  }

  // a function to change the admin password. returns true on success, false on failure
  // this only checks if the $old password is correct. it's up to the code that calls this to verify
  // things like password strength, length, etc.

  function change_passwd($old, $new)
  {
    return $this->str_to_bool($this->do_raw_query('passwd ' . $old . ' ' . $new));
  }

  // shift off and return the array of the current data query. array_shift returns FALSE if dat is empty.
  // The & operator makes $data a reference to the incoming variable rathen then a copy... so we don't
  // go into an infinate loop

  function data_fetch_array(& $data) { return array_shift($data); }

  // return the number of rows of the last data query

  function data_num_rows() { return $this->num_rows; }

  // return the insert id of the last data query, 0 if none

  function data_insert_id() { return $this->insert_id; }

  // return the number of rows affected by the last query (update, delete). 0 if none

  function data_rows_affected() { return $this->rows_affected; }

  // tell us if we have a database connection. the results of this function are cached,
  // so we don't keep asking the socket for every single query. If the connection dies in the
  // middle of a page load, an error will be issued via the do_raw_query call

  function data_alive() {
    
    // if $this->alive is already set, don't bother asking again

    if( ! $this->alive )
      {
	$this->alive = $this->str_to_bool($this->do_raw_query('connect'));
      }
    
    return $this->alive;
    
  }

} // end class rcsock 

?>