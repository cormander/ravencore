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
	function rcclient() {

		global $socket_err;

		// set our socket variables...
		$this->ETX = chr(3); // end of text
		$this->EOT = chr(4); // end of transmission

		// TODO: make this dynamic off of getcwd
		$socket = '/usr/local/ravencore/var/rc.sock';

		list($major, $minor, $release) = explode('.',phpversion());

		// php5 requires that "unix://" be prepended to the socket path
		if($major == 5) $socket = 'unix://' . $socket;

		// open our socket...
		$this->sock = @fsockopen($socket, 0, $errno, $errstr);

		// make sure our return value is a rea resource... if not, we shouldn't continue
		if(!is_resource($this->sock)) {

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

		//
		$this->status_mesg = array();

		// auth $session_id $ipaddress $username $password
		$this->auth_resp = $this->do_raw_query(
							'auth ' .
							 $this->session_id . ' ' .
						 $_SERVER['REMOTE_ADDR'] .
						 ( $_POST['user'] ? ' ' . $_POST['user'] . ' ' . $_POST['pass'] : '' )
						);

	}

	// submit a query to the socket, and return the raw data that is the answer.
	function do_raw_query($query, $serial = NULL) {

		// submit the query to the socket, base64 encoded to be binary safe, and the EOT character tells
		// the socket that we're done transmitting this query
		fwrite($this->sock, $query . ( $serial ? ' -- ' . base64_encode(serialize($serial)) : '') . $this->EOT);

		// flush our writting to the socket so we get an imidate reply on the data
		fflush($this->sock);

		// read data until the EOT byte
		// the $data .= $c; comes FIRST so that $data won't have an EOT byte at the end of it,
		// when the string is done being built

		$c = "";
		$data = "";
		$error = "";

		while ( $c != $this->EOT ) {

			$data .= $c;

			$c = fgetc($this->sock);


			// if $c is litterally false, we got disconnected. probably a "too many connections" error
			if ($c === false) {

				// nav_top/bottom may not exist yet if we got a disconnect error before auth.php is executed
				if(function_exists('nav_top')) nav_top();

				// TODO: FIX FIX FIX
				print 'Please wait a few seconds and then click <a href="javascript:refresh()">here</a>';

				if(function_exists('nav_bottom')) nav_bottom();

				exit;
		  	}

		}

		$output = unserialize(base64_decode($data));

		if ( $output['stderr'] ) {
			foreach ($output['stderr'] as $err) {
				array_push($this->status_mesg, $err);
			}
		}

		// return the raw response. whichever function calling run will parse the data
		// as appropriate
		return $output['stdout'];

	}

	// query the database with $sql statement, and return the results to be retrieved with
	// data_fetch_array(). This returns an actual array of arrays, instead of a resource or
	// a pointer like most other database objects, so keep that in mind.
	function data_query($sql) {

		// query the socket and get the data based on our question
		$data = $this->do_raw_query('sql ' . $sql);

		$this->insert_id = $data['insert_id'];
		$this->rows_affected = $data['rows_affected'];

		// set our row count to zero
		$this->num_rows = count($data['rows']);

		// return the nested array
		return $data['rows'];
	}

	// a function to change the current database in use
	function use_database($database) {
		return $this->do_raw_query('use ' . $database);
	}

	// a function to change the admin password. returns true on success, false on failure
	// this only checks if the $old password is correct. it's up to the code that calls this to verify
	// things like password strength, length, etc.
	function change_passwd($old, $new) {
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

}

// set our custom session handleing functions:
// these make the php code NOT keep the session_id as a file readable by the webserver user. Instead, it provides a
// method of storing session information (data, acess times, usernames, and the session id itself) in a root-read only
// file. This way it becomes incredibly difficult for a hacker on the system (with webserver permissions - the rcadmin
// user) to determine the session ID of a user, and thus, have their privileges. The only way to find out their session
// information is either hacking that user's computer, intercepting their browser's communication (that should be over
// ssl anyway), or via social engeneering - "hey, whats the ID of your ravencore session?" - LOL

session_set_save_handler("session_open", "session_close", "session_read", "session_write", "session_dest", "session_gc");

//
function session_open($save_path, $session_name) {
	global $rcdb;

	// TODO: use $session_name on the socket to support third party apps' use of it

	// create the class
	$rcdb = new rcclient;

	return true;
}

// close the session
function session_close() {
  // the socket closes the file when written to, so no need to do anything here
  return true;
}

// ask the socket for data.. since the socket already knows who we are, we really don't even need to pass the $id to
// it. the socket will already have the data in memory - it read it in the auth function - it'll have it cached and
// just return whats there
function session_read($id) {
	global $rcdb;

	$data = $rcdb->do_raw_query('session_read');

	return ( $data ? $data : "" );
}

// tell the socket to store $sess_data
function session_write($id, $sess_data) {
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
function session_dest($id) {
	global $rcdb;

	$rcdb->do_raw_query('session_dest User logging out');

	$_SESSION = array();

	return true;
}

// TODO: can someone please tell me wtf this function is for ??? (got it off php.net)
function session_gc($maxlifetime) {
	return true;
}

