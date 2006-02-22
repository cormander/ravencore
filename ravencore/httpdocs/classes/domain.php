<?php

class domain {

  function domain($did) {

    global $db;

    $this->did = (int)$did;

    // get domain table info

    $sql = "select * from domains where id = '" . $this->did . "' limit 1";
    $result =& $db->Execute($sql);
    
    $this->info =& $result->FetchRow();

    // get all of this domain's email addresses

    $sql = "select * from mail_users where did = '" . $this->did . "'";
    $result =& $db->Execute($sql);

    // get number of email addresses
    
    $this->info['num_emails'] = $result->RecordCount();    

    // fill an array with email addresses

    $this->info['emails'] = array();

    for ( $i = 0; $row =& $result->FetchRow(); $i++ )
      {
	
	$key = $row['id'];
	
	$this->info['emails'][$key] = $row;
	
      }
    
    // get all of this domains' sys users

    $sql = "select * from sys_users s, domains d where d.id = '" . $this->did . "' and s.id = d.suid";
    $result =& $db->Execute($sql);
    
    // get number of sys users

    $this->info['num_sys_users'] = $result->RecordCount();

    // fill an array with sys users

    $this->info['sys_users'] = array();

    for ( $i = 0; $row =& $result->FetchRow(); $i++ )
      {

	$key = $row['id'];

	$this->info['sys_users'][$key] = $row;

      }

  }

  //

  function space_usage($month, $year)
  {

    global $db;

    $total = 0;

    $arr[0] = "web";
    $arr[1] = "mail";
    $arr[2] = "database";

    foreach($arr as $type)
      {
        $sql = "select * from domain_space where did = '" . $this->did . "' and type = '" . $type . "' and month(date) = '" . $month . "' and year(date) = '" . $year . "' order by date desc limit 1";
        $result =& $db->Execute($sql);

        $row =& $result->FetchRow();

        $total += $row['bytes'];

      }
    
    return ereg_replace(",", "", number_format($total / 1024 / 1024, 2));

  }

  // get a domains usage of traffic

  function traffic_usage($month, $year)
  {

    global $CONF, $db;

    $d = new domain($this->did);

    $domain_name = $d->name();

    // $prog = "awk '/^BEGIN_DOMAIN/ { getline; print $4 }' " . $CONF[RC_ROOT] . "/var/lib/awstats/awstats" . $month . $year . "." . $domain_name . ".txt";
    // $prog = "awk '/^BEGIN_DOMAIN/ { while(1) { getline; if($1 == \"END_DOMAIN\") exit; print $4 } }' " . $CONF[RC_ROOT] . "/var/lib/awstats/awstats" . $month . $year . "." . $domain_name . ".txt";
    $dir = $CONF['VHOST_ROOT'] . '/' . $domain_name . '/var/awstats/awstats' . $month . $year . '.' . $domain_name . '.txt';
    
    if (file_exists($dir))
      {
        $prog = 'total=0; for i in `awk \'/^BEGIN_DOMAIN/ { while(1) { getline; if($1 == "END_DOMAIN") break; else print $4; } }\' ' . $dir . '`; do total=$(expr $i "+" $total); done; for i in `awk \'/^BEGIN_ROBOT/ { while(1) { getline; if($1 == "END_ROBOT") break; else print $3; } }\' ' . $dir . '`; do total=$(expr $i "+" $total); done; for i in `awk \'/^BEGIN_ERRORS/ { while(1) { getline; if($1 == "END_ERRORS") break; else print $3; } }\' ' . $dir . '`; do total=$(expr $i "+" $total); done; echo $total';

        $handle = popen($prog, 'r');

        while (!feof($handle)) $prog_data .= fread($handle, 1024);

        pclose($handle);
	    }
	  else $prog_data = 0;

    $sql = "select sum(bytes) as traffic from domain_traffic t, domains d where did = d.id and month(date) = '" . $month . "' and year(date) = '" . $year . "' and d.id = '" . $this->did . "'";
    $result =& $db->Execute($sql);

    $row =& $result->FetchRow();

    $prog_data += $row['traffic'];

    return ereg_replace(",", "", number_format($prog_data / 1024 / 1024, 2));

  }

  //

  function name()
  {

    return $this->info['name'];

  }

  function delete_email($mid) {

    global $db;

    $sql = "delete from mail_users where did = '" . $this->did . "' and id = '" . $mid . "'";
    $db->Execute($sql);

    $this->info['emails'][$mid] = NULL;

    // rehash_mail

  }

  function delete_hosting()
  {
    
    global $db;
    
    // delete all the system users
    $sql = "delete from sys_users where id = '" . $this->info['suid'] . "'";
    $db->Execute($sql);
    
    foreach( $this->info['sys_users'] as $key => $val ) socket_cmd("ftp_del " . $val['login'] );
    
    $sql = "update domains set host_type = 'none' where id = '" . $this->did . "'";
    $db->Execute($sql);
    
    socket_cmd("domain_del " . $this->info['name'] );
    
  }

  function delete()
  {

    global $db;

    // delete all the email
    $sql = "delete from mail_users where did = '" . $this->did . "'";
    $db->Execute($sql);

    // delete all the DNS records
    $sql = "delete from dns_rec where did = '" . $this->did . "'";
    $db->Execute($sql);

    $this->delete_hosting();

    // TODO:
    // delete databases
    // 

    // delete the domain    
    $sql = "delete from domains where id = '" . $this->did . "'";
    $db->Execute($sql);

    // run the nessisary system calls

    socket_cmd("rehash_named --rebuild-conf --all");

    socket_cmd("rehash_mail --all");

  }



} // end class domain


?>