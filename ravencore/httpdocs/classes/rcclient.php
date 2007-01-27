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


class rcclient

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

class rcclient {

  // open the socket connection when the object is created

  function rcclient()
  {
    
    global $socket_err;

    // set our socket variables...

    $this->ETX = chr(3); // end of text
    $this->EOT = chr(4); // end of transmission
    $this->NAK = chr(21); // negative acknowledge
    $this->ETB = chr(23); // end of trans. block
    $this->CAN = chr(24); // cancel

    // TODO: make this dynamic off of getcwd
    $socket = '/usr/local/ravencore/var/rc.sock';

    list($major, $minor, $release) = explode('.',phpversion());

    // php5 requires that "unix://" be prepended to the socket path
    if($major == 5) $socket = 'unix://' . $socket;

    // open our socket...

    $this->sock = fsockopen($socket, 0, $errno, $errstr);
    
    // make sure our return value is a rea resource... if not, we shouldn't continue
    
    if(!is_resource($this->sock))
      {

	nav_top();
	
	print 'Unable to open socket: ' . $socket . '<br>Error code: ' . $errno . ' - ' . $errstr . '<p>Please restart the control panel';

	nav_bottom();

	exit;

      }
    
    // RavenCore sessions are cookie based only. If there is no "RAVENCORE" $_COOKIE, then we don't have a session id,
    // and we need to generate one (with the md5 sum of a very random number)
    $this->session_id = ( $_COOKIE['RAVENCORE'] ? $_COOKIE['RAVENCORE'] : md5(uniqid(rand(), true)) );
    
    // set the session ID
    session_id($this->session_id);
    
    // auth $session_id $ipaddress $username $password
    $this->auth_resp = $this->do_raw_query('auth ' .
					 $this->session_id . ' ' .
					 $_SERVER['REMOTE_ADDR'] .
					 ( $_POST['user'] ? ' ' . $_POST['user'] . ' ' . $_POST['pass'] : '' )
					 );

  }

  // submit a query to the socket, and return the raw data that is the answer.

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

      // if $c is litterally false, we got disconnected. probably a "too many connections" error
      if( $c === false )
	{

	  // nav_top/bottom may not exist yet if we got a disconnect error before auth.php is executed
	  if(function_exists('nav_top')) nav_top();

	  print 'ERROR: Broken pipe on socket, or too many connections.';

	  if(function_exists('nav_bottom')) nav_bottom();

	  exit;
	}
      
    } while ( $c != $this->EOT );

    // check for an error on the data returned.

    // if the first character in $data is a NAK byte,
    // then we know there was trouble.... print the error and exit. We don't have to worry about
    // binary files fudging this up, because just about everything else is returned as a base64
    // encoded string

    if( preg_match('|^' . $this->NAK . '.*' . $this->ETB . '|', $data) )
      {

	// this error is a fatal error if it ends with the CAN byte
	if (  preg_match('|' . $this->CAN . '$|', $data) )
	  {
	    nav_top();
	    print str_replace($this->CAN,'',$data);
	    nav_bottom();
	    exit;
	  }

	$error = preg_replace('|^' . $this->NAK . '(.*)' . $this->ETB . '.*$|s','\1',$data);
	$data = preg_replace('|^' . $this->NAK . '.*' . $this->ETB . '(.*)$|s','\1',$data);

	// TODO: make this call a function instead. and whether or not headers are already sent, either 
	// add this to the session or just output it right away. make this session an array rather then
	// just a string, so we can clearly count the number of errors if we need to

	$_SESSION['status_mesg'] = 'ERROR on query: ' . $query . '<br />Server responded with: ' . $error;

      }

    // return the raw response. whichever function calling do_raw_query will parse the data
    // as appropriate

    return unserialize(base64_decode($data));

  }
  
  // query the database with $sql statement, and return the results to be retrieved with
  // data_fetch_array(). This returns an actual array of arrays, instead of a resource or
  // a pointer like most other database objects, so keep that in mind.
  
  function data_query($sql)
  {

    // query the socket and get the data based on our question
    $data = $this->do_raw_query('sql ' . $sql);

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

	// we don't do an array_pop here, because the last ETX was removed by the first explode
	// where the end-of-row one and the end-of-column ones were joined, which is why we split on two
	// the end of the string here is an actual value to consider in the array
	$i = $this->num_rows;

	// walk down the raw column data, as we still have yet to split into key / val
	foreach($item as $item_data)
	  {
	    
	    // data is returned in the following format:
	    // key{value} ( value is base64 encoded )
	    // so the two below regex rules parse out the key / val appropriatly
 	    
	    $key = preg_replace('|^(.*)\{.*\}$|s','\1',$item_data);
	    $val = preg_replace('|^.*\{(.*)\}$|s','\1',$item_data);

	    // return the : characters back to newline, and decode the base64 of $val to get the real value
	    $dat[$i][$key] = base64_decode($val);
	    
	  } // end foreach($item as $item_data)
	
	// increment the row number
	$this->num_rows++;
	
      } // end foreach($rows as $row_data)

    // return the nested array
    return $dat;

  } // end function data_query($sql)

  // a function to change the current database in use

  function use_database($database)
  {
    return $this->do_raw_query('use ' . $database);
  }

  // a function to change the admin password. returns true on success, false on failure
  // this only checks if the $old password is correct. it's up to the code that calls this to verify
  // things like password strength, length, etc.

  function change_passwd($old, $new)
  {
    return $this->do_raw_query('passwd ' . $old . ' ' . $new);
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

} // end class rcclient

// set our custom session handleing functions:
// these make the php code NOT keep the session_id as a file readable by the webserver user. Instead, it provides a
// method of storing session information (data, acess times, usernames, and the session id itself) in a root-read only
// file. This way it becomes incredibly difficult for a hacker on the system (with webserver permissions - the rcadmin
// user) to determine the session ID of a user, and thus, have their privileges. The only way to find out their session
// information is either hacking that user's computer, intercepting their browser's communication (that should be over
// ssl anyway), or via social engeneering - "hey, whats the ID of your ravencore session?" - LOL

session_set_save_handler("session_open", "session_close", "session_read", "session_write", "session_dest", "session_gc");

//

function session_open($save_path, $session_name)
{
  global $rcdb;

  // TODO: use $session_name on the socket to support third party apps' use of it

  // create the class
  $rcdb = new rcclient;

  return true;
}

// close the session

function session_close()
{
  // the socket closes the file when written to, so no need to do anything here
  return true;
}

// ask the socket for data.. since the socket already knows who we are, we really don't even need to pass the $id to
// it. the socket will already have the data in memory - it read it in the auth function - it'll have it cached and
// just return whats there

function session_read($id)
{
  global $rcdb;

  $data = $rcdb->do_raw_query('session_read');

  return ( $data ? $data : "" );
}

// tell the socket to store $sess_data

function session_write($id, $sess_data)
{
  global $rcdb;

  // open / write / close the file

  return $rcdb->do_raw_query('session_write ' . $sess_data);

  // TODO: return # of bytes written, or false

  // TODO: don't allow session data to take up more then 512k of disk space on the filesystem.
  // an admin session bypasses this check - if someone has admin access, the last thing we need to worry about
  // is them taking up all the disk space with session files. add a "kill all sessions but my own" function
  // server-side, only write up to a certian amount of data - if gone over, issue an error. Also, check the
  // number of sessions - if over 512, then we have a TON of session files - issue error:
  // "too many sessions"

  // TODO: add an hourly cron to check all session files, and remove expired ones

}

// tell the socket to remove all data for this session

function session_dest($id)
{
  global $rcdb;

  $rcdb->do_raw_query('session_dest User logging out');

  return true;
}

// TODO: can someone please tell me wtf this function is for ??? (got it off php.net)

function session_gc($maxlifetime)
{
  return true;
}

