<?php

class server {

  function server()
  {

    global $CONF;

    $this->db_panic = false;

    // Get our conf vars
    $this->read_db_conf();

    // find out what distribution of linux we are
    $dist_map = file("../etc/dist.map");

    foreach($dist_map as $distro)
      {
	$arr = explode(' ',$distro);

	// the first one is the dist name, we maybe are this one
	$maybe_this = trim(array_shift($arr));

	// don't bother checking this if it's a # or a blank

	if($maybe_this == '#' or $maybe_this == '') continue;

	// walk down the rest of the array.. they are files to check for
	foreach($arr as $file)
	  {
	    // if this file exists, we're this dist. finish off
	    if(file_exists(trim($file)) and !$this->dist)
	      $this->dist = $maybe_this;
	    
	  }

      }

    // on installation, we won't have a default locale. Set it to the system EV
    // set our language
    if (isset($_REQUEST['lang']) or isset($_SESSION['lang']))
      {
	$_SESSION['lang'] = ($_REQUEST['lang'] ? $_REQUEST['lang'] : $_SESSION['lang']);
	
	locale_set($_SESSION['lang']);
	
      }
    
    // Check to see if the database conf file exists
    if (!file_exists("$CONF[RC_ROOT]/database.cfg"))
      {
	nav_top();
	
	print __('You are missing the database configuration file: ' . $CONF['RC_ROOT'] . '/database.cfg<p>Please run the following script as root:<p>' . $CONF['RC_ROOT'] . '/sbin/database_reconfig');

	nav_bottom();
	
	exit;
      }

  }

  function db_panic()
  {

    $this->db_panic = true;

  }

  function get_all_services()
  {

    $services = array();

    $h = popen("ls ../etc/services.*", "r");

    while (!feof($h)) $data .= fread($h, 1024);

    pclose($h);

    $modules = explode("\n", $data);
    // get rid of the last line which is blank
    array_pop($modules);

    foreach($modules as $module)
      {
        // figure out the service name
        $service = preg_replace('|\.\./etc/services\.|', '', $module);

        // only show if the +x bit is set on the conf.d file
        if (is_executable("../conf.d/$service.conf"))
	  {
            $data = '';
            $tmp = array();

            $h = fopen($module, "r");

            while (!feof($h)) $data .= fread($h, 1024);

            fclose($h);

            $tmp = explode("\n", $data);

            // get rid of the last line which is blank
            array_pop($tmp);

            // looks likephp < 4.3 doesn't fill the $_ENV array. set the default here if it doesn't exist
            if (!$_ENV['INITD'])
	      {
		$_ENV['INITD'] = '/etc/init.d';
	      }

            // only add services to the array we return if an init script exists for it
            foreach($tmp as $service)
	      {
		if (file_exists($_ENV['INITD'] . '/' . $service))
		  {
		    array_push($services, $service);
		  }
	      }
	  }
      }

    return $services;
  }

  // A function to tell us if we have any "domain" services, or in other words,
  // any service that requires the use of the domains table to function
  function domain()
  {
    // domain services as of version 0.0.1
    if ($this->module_enabled("web") or $this->module_enabled("mail") or $this->module_enabled("dns")) return true;

    return false;

  }
  // A function to tell us whether or not we have database services.
  // Right now it only looks for mysql, but this will in the future look for other db types
  // such as pgsql, mssql, etc
  function database()
  {
    if (have_service("mysql")) return true;
    else return false;
  }

  // tell us if a module is "enabled" ( baiscally, is the conf file executable )
  function module_enabled($service)
  {
    global $CONF;

    if (
	is_executable($CONF[RC_ROOT] . "/conf.d/" . $service . ".conf")
	and
	!file_exists($CONF[RC_ROOT] . "/conf.d/" . $service . ".conf.ignore")
	) return true;
    else return false;
  }

  // A function that requires the existances of the webserver to load the page
  // Must use before you output any headers.
  function req_module($service)
  {
    if ( ! $this->module_enabled($service) )
      {
        nav_top();

        print __('This server does not have ' . $service . ' installed. Page cannot be displayed.');

        nav_bottom();

        exit;

      }

  }

  // this function takes an array of files, reads them, and parses for variable names we require
  // the files in the given array must be a full or relative path to ravencore's httpdocs !
  function build_conf_array($files)
  {

    $conf_array = array();

    foreach($files as $file)
      {
	// check for .dist files here, and use it instead if it exists
	if(file_exists($file . '.' . $this->dist)) $file .= '.' . $this->dist;
	// check for .ignore files here, and skip this conf file if it exists
	if(file_exists($file . '.ignore')) continue;
	// merge the contents of this file with our array
	$conf_array = array_merge($conf_array,file($file));
      }

    $arr = array();

    foreach($conf_array as $line)
      {
        // Get rid of quotation marks
        $line = ereg_replace("\"", "", $line);
        $line = ereg_replace("\'", "", $line);
	$line = trim($line);
        // If this looks like a bash shell variable, make it into a php variable. All conf
        // variables will be all upper case. If they are not, they won't get read in here.
        if (preg_match("/^[A-Z||_]*=/", $line))
          {
            $var_name = ereg_replace("=.*", "", $line);

            $var_value = ereg_replace(".*=", "", $line);
            // Set the conf array with the name and value of this variable
            $arr[$var_name] = $var_value;

	    //print $var_name . ' = ' . $var_value . '<br>';
          }
      }

    return $arr;

  }

  // A function to read in our base configuration variables
  function read_db_conf()
  {
    global $CONF;
    // Open configuration files and read in all the data.
    $CONF = $this->build_conf_array(array('/etc/ravencore.conf','../database.cfg'));
  }

  // A function to read in our database configuration variables
  function read_conf()
  {
    global $CONF, $conf_not_complete, $db;

    // return pre-maturely if no database connection
    if( $this->db_panic ) return;

    // get our settings from the database
    $sql = "select * from settings";
    $result = $db->data_query($sql);

    while ($row = $db->data_fetch_array($result))
      {
        $key = $row['setting'];
        $val = $row['value'];
        // load the info into the global CONF array
        $CONF[$key] = $val;
      }

    // Open configuration files and read in all the data.
    $d = opendir('../conf.d/');
    while (($file = readdir($d)) !== false) {
      if(is_executable('../conf.d/'.$file) and ereg('\.conf$',$file)) $files[] = '../conf.d/' . $file;
    }
    closedir($d);

    $arr = $this->build_conf_array($files);

    foreach( $arr as $key => $val )
      {	
	if ($key == "SERVER_TYPE")
	  {
	    $CONF[$key] = $val;
	  }
	else if ( ! isset($CONF[$key]) )
	  {
            $conf_not_complete = true;
	  }
      }
    
    // get this version number
    if (file_exists("../etc/version"))
      {
        $handle = fopen("../etc/version", "r");

        while (! feof($handle)) $version_data .= fread($handle, 1024);

        fclose($handle);

        $CONF['VERSION'] = trim($version_data);
      }
    else
      {
        $CONF['VERSION'] = '';
      }
  }


  function check_version()
  {

    global $CONF, $conf_not_complete;

    // look in our misc table to see if we should lock the control panel to users if our version is outdated
    // only bother checking if the configuration is complete
    if (($CONF['LOCK_IF_OUTDATED'] == 1) && !$conf_not_complete && ($CONF['SERVER_TYPE'] != 'slave'))
      {
        // set the timeout for the tcp connection, lucky 7
        $timeout = 7;

        if ($CONF['VERSION'] and $fsock = @fsockopen('www.ravencore.com', 80, $errno, $errstr, $timeout))
	  {
            // set the timeout for reading / writting data to the socket
            if (function_exists(stream_set_timeout))
	      {
                stream_set_timeout($fsock, $timeout);
	      }
	    
            $general_version = preg_replace('/\.\d*$/', '.x', $CONF['VERSION']);

            @fputs($fsock, "GET /updates/" . $general_version . ".txt HTTP/1.1\r\n");
            @fputs($fsock, "HOST: www.ravencore.com\r\n");
            @fputs($fsock, "Connection: close\r\n\r\n");

            while (!@feof($fsock))
	      {
                $data .= @fread($fsock, 1024);
	      }

            @fclose($fsock);

            $http_info = explode("\r\n", $data);
            // this will always be blank
            // array_pop($http_info);
            $current_version = trim(array_pop($http_info));
            // echo "$general_version -- $current_version -- $CONF[VERSION]";
            list($x, $y, $current_version) = explode('.', $current_version);
            list($x, $y, $conf_version) = explode('.', $CONF['VERSION']);

            if ($current_version > $conf_version)
	      {
		return false;
	      }
	  }
      }

    return true;

  }



  function install_checks()
  {

    global $CONF, $db, $conf_not_complete, $action, $server;

    // make sure the admin password doesn't stay as "ravencore"
    if ( $db->data_auth('ravencore') && is_admin() &&
        ($_SERVER['PHP_SELF'] != '/change_password.php') &&
        ($_SERVER['PHP_SELF'] != '/logout.php'))
      {
	// tell the change_password file that it is being included, rather than called from the browser directly
	$being_included = true;

	include "change_password.php";

	exit;

      }

    // make the user agrees to the GNU GPL license for using RavenCore
    // just in case the gpl_check file gets removed, you can still logout
    if (!file_exists("../var/run/gpl_check") and is_admin() and $_SERVER['PHP_SELF'] != "/logout.php")
      {
	if ($action == "gpl_agree" and $_POST['gpl_agree'])
	  {
	    shell_exec("touch ../var/run/gpl_check");
	  }
	else
	  {
	    nav_top();

	    if ($action == "gpl_agree")
	      {
		print '<b><font color="red">' . __('You must agree to the GPL License to use RavenCore.') . '</font></b><p>';
	      }

	    print __('Please read the GPL License and select the "I agree" checkbox below') . '<hr><pre>';

	    $h = fopen("../LICENSE", "r");

	    fpassthru($h);

	    print __('The GPL License should appear in the frame below') . ': </pre>';

        ?>
                <iframe src="GPL" width="675" height="250">
                </iframe>
                <p>
                <form method="post">
		   <input type="checkbox" name="gpl_agree" value="yes"> <?php echo __('I agree to these terms and conditi
ons.') ?>

                        <p>

                        <input type="submit" value="Submit"> <input type="hidden" name="action" value="gpl_agree">
                </form>
                <?php

		   nav_bottom();

	    exit;

	  }
      }

    // check to make sure we have a complete database configuration. If not, prompt the admin
    // user to update it, and lock out the rest of the users
    if ($conf_not_complete and $_SERVER['PHP_SELF'] != "/change_password.php" and $_SERVER['PHP_SELF'] != "/logout.php")
      {
	if (is_admin())
	  {
	    if ($action != "update_conf")
	      {
		nav_top();
	      }
	    // if we have $action, that means we're being posted to. Don't print anything
	    if ($action != "update_conf")
	      {
		print '<div align=center>' . __('Welcome, and thank you for using RavenCore!') . '</div>
                        <p>
                        ' . __('You installed and/or upgraded some packages that require new configuration settings.') .
		  __('Please take a moment to review these settings. We recomend that you keep the default values, ') .
		  __('but if you know what you are doing, you may adjust them to your liking.') . '
                        <div align=center>
                        <form method=post>
                        <input type=hidden name=action value="update_conf">
                        <table>';
	      }

	    $data = "";

	    // get a list of services that are enabled
	    $d = opendir('../conf.d/');
	    while (($file = readdir($d)) !== false) {
	      if(is_executable('../conf.d/'.$file) and ereg('\.conf$',$file)) $files[] = '../conf.d/' . $file;
	    }
	    closedir($d);

	    foreach ($files as $conf_file)
	      {
		if (!$conf_file)
		  {
		    continue;
		  }
		// reset whether we have printed this conf file's name yet
		$printed_header = false;

		// build the file's contents into an array
		
		$arr = $this->build_conf_array(array($conf_file));
		
		foreach( $arr as $key => $val )
		  {
		    // check to make sure we have this variable
			if (!$CONF[$key] and $action != "update_conf")
			  {
			    // only print "conf configuration" if we are not posting variables, and if we haven't printed
			    // something of this category yet
			    if ($action != "update_conf" and !$printed_header)
			      {
				print '<tr><th colspan=2 align=center>' . ereg_replace('\.\./conf\.d\/','',ereg_replace('\.conf.*$', "", $conf_file)) . ' ' . __('configuration') . '</th></tr>';
				
				$printed_header = true;
			      }
			    
			    print '<tr><td>' . $key . ':</td><td>';
			    // values with | character are considered to be a list of possible values
			    if (@ereg('\|', $val))
			      {
				print '<select name="' . $key . '">';

				foreach(explode('|', $val) as $e)
				  {
				    print '<option value="' . $e . '">' . $e . '</option>';
				  }

				print '</select>';
			      }
			    else print '<input name="' . $key . '" value="' . $val . '">';

			    print '</td></tr>';
			  }

			if (($_POST[$key]) and $action == "update_conf" and ! $server->db_panic)
			  {
			    // insert this into the database
			    $sql = "insert into settings set setting = '" . $key . "', value = '" . $_POST[$key] . "'";
			    $db->data_query($sql);
			    
			  }
		  }
	      }
	    
	    if ($action != "update_conf")
	      {
		print '<tr><td colspan=2 align=right><input type="submit" value="' . __('Submit') . '"></td></tr></table></div>';
		
		nav_bottom();

		exit;
	      }
	    else
	      {
		$url = $_SERVER['PHP_SELF'];

		if ($_SERVER['QUERY_STRING']) $url .= '?' . $_SERVER['QUERY_STRING'];

		goto($url);
	      }
	  }
	else
	  {
	    $login_error = __('Control Panel is being upgraded. Login Locked.');

	    include "login.php";

	    exit;
	  }
      }
    
    if (!file_exists('../var/run/install_complete'))
      {
	if (have_service("web"))
	  {
	    socket_cmd("rehash_ftp --all");
	    socket_cmd("rehash_httpd --all");
	  }

	if (have_service("mail"))
	  {
	    socket_cmd("rehash_mail --all");
	  }

	if (have_service("dns"))
	  {
	    socket_cmd("rehash_named --all --rebuild-conf");
	  }

	shell_exec('touch ../var/run/install_complete');
      }

  }

}

?>