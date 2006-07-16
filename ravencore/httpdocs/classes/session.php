<?php

class session {

  function session()
  {

    global $db;

    // make sure PHP was compiled with sessions enabled
    if (!function_exists("session_start"))
      {
	nav_top();
	
	print __('The server doesn\'t have PHP session functions available.<p>Please recompile PHP with sessions enabled.');
	
	nav_bottom();

	exit;

      }

    session_start();

    $this->id = session_id();

    // always false, unless otherwise set by another function

    $this->admin_user = false;
    $this->logged_in = false;
    
  }

  function do_auth($username, $password)
  {

    global $CONF, $db, $server;

    if( ! $server->db_panic )
      {
	if ( $CONF['LOCKOUT_TIME'] )
	  {
	    $lockout_time = $CONF['LOCKOUT_TIME'];
	  }
	else
	  {
	    $lockout_time = 300;
	  }

	// check the lockout to see if we have several failed attempts
	$sql = "select count(*) as count from login_failure
                        where login = '" . $username . "' and
                                ( ( to_days(date) * 24 * 60 * 60 ) + time_to_sec(date) + " . $lockout_time . " ) >
                                ( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) )";

	$result = $db->data_query($sql);
	
	$row = $db->data_fetch_array($result);
	
	if( $row['count'] >= $CONF['LOCKOUT_COUNT'] and $CONF['LOCKOUT_COUNT'])
	  {
	    
	    $this->login_error = __('Login locked.');
	    
	    return false;
	    
	  }

	// end lockout check

      }

    // now we do password authentication
    
    switch($username)
      {
	
      case $CONF['MYSQL_ADMIN_USER']:
	
	// admin user auth

	if ( ! $db->data_auth($password) )
	  {

	    // admin login faliure

	    $this->login_error = __('Login failed.');

	    return false;

	  }
	
	// set username / password sessions if db_panic is true
	if ( $server->db_panic )
	  {

	    $_SESSION['username'] = $username;
	    $_SESSION['password'] = $password;

	  }

	$this->admin_user = true;

	break;
	/*
      case ( @ereg('@', $username) ):

	// email user auth

	list($login_mailname, $login_domain) = split("@", $username);

	$sql = "select m.id, d.name, uid, did from mail_users m, domains d where mail_name = '" . $login_mailname . "' and name = '" . $login_domain . "' and m.passwd = '" . $password . "' limit 1";
	$result =& $db->Execute($sql);

	if ( $result->RecordCount() != 1 )
	  {

	    $this->login_error = __('Login failure.');

	    return false;
	    
	  }

	break;
	*/
      default:

	// normal user auth

	if( $server->db_panic )
	  {
	    $this->login_error = __('Database Error');

	    return false;
	  }

        $sql = "select * from users where binary(login) = '" . $username . "' and binary(passwd) = '" . $password . "' limit 1";
        $result = $db->data_query($sql);

        if ( $db->data_num_rows() != 1 )
	  {
            $this->login_error = __('Login failure.');

            return false;
	  }

	break;

      }

    // we get here on success
    $this->logged_in = true;

    if( ! $server->db_panic )
      {
	$sql = "insert into sessions set session_id = '" . $this->id . "', login = '" . $username . "', location = '" . $_SERVER['REMOTE_ADDR'] . "', created = now(), idle = now()";
	$db->data_query($sql);
      }

    syslog(LOG_INFO, "User " . $username . " logged in from " . $_SERVER[REMOTE_ADDR]);

    return true;
    
  }

  // make sure the given session exists and isn't idle too long
  
  function found()
  {

    global $db, $CONF, $server;

    // Define a default value for SESSION_TIMOUT here, because if we don't have one at all,
    // we'll never be able to login
    if (!$CONF['SESSION_TIMEOUT'])
      {
	$session_timeout = 600;
      }
    else
      {
	$session_timeout = $CONF['SESSION_TIMEOUT'];
      }

    // an admin user was logged in with a db_panic, and now there is no db_panic
    if($_SESSION['username'] == $CONF['MYSQL_ADMIN_USER'] and
       $db->data_auth($_SESSION['password']) and ! $server->db_panic )
      {

	// give them a database session
        $sql = "insert into sessions set session_id = '" . $this->id . "', login = '" . $_SESSION['username'] . "', location = '" . $_SERVER['REMOTE_ADDR'] . "', created = now(), idle = now()";
	$db->data_query($sql);
	
	// remove their db_panic session
	unset($_SESSION['username']);
	unset($_SESSION['password']);
	
      }
       
    // if server is in db_panic mode, admin can still login    
    if( $server->db_panic )
      {

        // admin user auth

        if ( $_SESSION['username'] != $CONF['MYSQL_ADMIN_USER'] or ! $db->data_auth($_SESSION['password']) )
          {
            // admin login faliure

            $this->login_error = __('Login failed.');

            return false;

          }

        $this->admin_user = true;

      }
    else
      {
	
	// remove old sessions
	$sql = "delete from sessions where ( ( to_days(idle) * 24 * 60 * 60 ) + time_to_sec(idle) + " . $session_timeout . " ) < ( ( to_days(now()) * 24 * 60 * 60 ) + time_to_sec(now() ) )";
	$db->data_query($sql);
	
	$sql = "select * from sessions where binary(session_id) = '" . $this->id . "' limit 1";
	$result = $db->data_query($sql);

	if ( $db->data_num_rows() != 1 )
	  {

	    return false;
	    
	  }

	$row_session = $db->data_fetch_array($result);

	// make sure the remote addr didn't change
	
	if ($row_session['location'] != $_SERVER['REMOTE_ADDR'])
	  {

	    syslog(LOG_WARNING, "Session hijack attempt detected for user $row[login] from $_SERVER[REMOTE_ADDR]");

	    // kill the session
	    $this->destroy();
	    
	    return false;
	    
	  }
	
	if ( $row_session['login'] == $CONF['MYSQL_ADMIN_USER'] )
	  {
	    $this->admin_user = true;
	  }

	// update your idle
	$sql = "update sessions set idle = now() where session_id = '" . $this->id . "'";
	$db->data_query($sql);

      }
    
    $this->logged_in = true;

    return true;
    
  }

  // find a user's ID based off the session

  function get_user_id()
  {

    global $db;

    $sql = "select u.id from users u, sessions s where binary(s.login) = binary(u.login) and s.session_id = '" . $this->id . "' limit 1";
    $result = $db->data_query($sql);

    $row = $db->data_fetch_array($result);

    return $row['id'];

  }
  
  // destroy a session
  function destroy()
  {

    global $db, $CONF;

    $sql = "delete from sessions where binary(session_id) = '" . $this->id . "'";
    $db->data_query($sql);

    // we want to keep the language session variable

    $lang = $_SESSION['lang'];

    $_SESSION = NULL;

    // kill the session data file
    
    @unlink($CONF['RC_ROOT'] . '/var/tmp/sess_' . $this->id);

    $_SESSION['lang'] = $lang;

    // destroy a phpMyAdmin session, if any

    session_destroy();
    session_name('phpMyAdmin');
    session_start();

    $_SESSION = NULL;
    @unlink($CONF['RC_ROOT'] . '/var/tmp/sess_' . session_id());
    
    session_destroy();

  }

  // end the session and save the data

  function end()
  {

    session_write_close();
    
  }

}

?>