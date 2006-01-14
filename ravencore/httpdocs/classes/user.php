<?php

class user {

  function user($uid) {

    global $db;

    $this->uid = (int)$uid;

    // get all info from user table

    $sql = "select * from users where id = '" . $this->uid . "' limit 1";
    $result =& $db->Execute($sql);

    $this->info =& $result->FetchRow();

    // get all domains for this user

    $sql = "select * from domains where uid = '" . $this->uid . "'";
    $result =& $db->Execute($sql);

    // get number of domains

    $this->info['num_domains'] = $result->RecordCount();

    // fill an array with domain ids

    $this->info['domains'] = array();

    for ( $i = 0; $row =& $result->FetchRow(); $i++ )
      {

        array_push($this->info['domains'], $row['id']);

      }

  }

  // get this user's usage of space

  function space_usage($month, $year)
  {
    
    global $db;
    
    $total = 0;

    foreach( $this->info['domains'] as $did )
      {
	
	$d = new domain($did);

	$total += $d->space_usage($month, $year);

      }

    return $total;
    
  }

  // get a users usage of traffic

  function traffic_usage($month, $year)
  {
  
    global $db;
    
    $total = 0;
    
    foreach( $this->info['domains'] as $did )
      {

        $d = new domain($did);

        $total += $d->traffic_usage($month, $year);

      }
    
    return $total;
    
  }

  // find out if a user owns this domains

  function owns_domain($did)
  {

    if( in_array( $did, $this->info['domains'] ) ) return true;
    else return false;

  }

  // return the number of domains this user has setup

  function get_num_domains()
  {

    return $this->info['num_domains'];

  }

  // delete this user
  
  function delete()
  {

    global $db;

    // delete this users domains
    foreach( $this->info['domains'] as $did )
      {

	$d = new domain($did);

	$d->delete();

      }

    // remove this users permissions
    $sql = "delete from user_permissions where uid = '" . $this->uid . "'";
    $db->Execute($sql);

    // get rid of the user
    $sql = "delete from users where id = '" . $this->uid . "'";
    $db->Execute($sql);

  }


} // end class user

?>