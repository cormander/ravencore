<?php

class user {

	function user($uid) {

		global $db;

		$this->uid = (int)$uid;

		// get all info from user table
		$sql = "select * from users where id = '" . $this->uid . "' limit 1";
		$result = $db->data_query($sql);

		$this->info = $db->data_fetch_array($result);

		// get all domains for this user
		$sql = "select * from domains where uid = '" . $this->uid . "'";
		$result = $db->data_query($sql);

		// get number of domains
		$this->info['num_domains'] = $db->data_num_rows();

		// fill an array with domain ids
		$this->info['domains'] = array();

		for ( $i = 0; $row = $db->data_fetch_array($result); $i++ ) {
			array_push($this->info['domains'], $row['id']);
		}
	}

	// get this user's usage of space
	function space_usage($month, $year) {

		global $db;

		$total = 0;

		foreach( $this->info['domains'] as $did ) {

			$d = new domain($did);

			$total += $d->space_usage($month, $year);

		}

		return $total;

	}

	// get a users usage of traffic
	function traffic_usage($month, $year) {

		global $db;

		$total = 0;

		foreach( $this->info['domains'] as $did ) {

			$d = new domain($did);

			$total += $d->traffic_usage($month, $year);

		}

		return $total;

	}

	// find out if a user owns this domains
	function owns_domain($did) {

		if( in_array( $did, $this->info['domains'] ) ) return true;
		else return false;

	}

	// return the number of domains this user has setup
	function get_num_domains() {
		return $this->info['num_domains'];
	}

}

?>
