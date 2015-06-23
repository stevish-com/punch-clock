<?php
function h2hm($hours) {
	if ( 0 < $hours ) {
		$minutes = round(($hours - floor($hours)) * 60);
		$rounded_hours = floor($hours);
	} elseif ( 0 > $hours ) {
		$minutes = round(($hours - ceil($hours)) * 60);
		$rounded_hours = ceil($hours);
	} else {
		$minutes = 0;
		$rounded_hours = 0;
	}
	return $rounded_hours . ':' . str_pad(abs($minutes), 2, "0", STR_PAD_LEFT);
}

function s_hash( $in ) {
	global $sql_pass;
	return sha1( sha1( md5( sha1( $in ) ) ) . $sql_pass );
}

function clock_in() {
	global $mysqli;
	//Create new job if necessary
	if(intval($_POST['job']) > 0) {
		$job = intval($_POST['job']);
	} elseif($_POST['newjob'] && $_POST['newrate']) {
		$jobname = preg_replace("/[^a-zA-Z0-9\s\.\,\-]/", "", $_POST['newjob']);
		$jobrate = intval($_POST['newrate']);
		$mysqli->query("INSERT INTO `jobs` (`name`, `rate`) VALUES ('$jobname', '$jobrate');");
		$job = $mysqli->insert_id;
	} else {
		error("Job Error: $job");
	}
	
	//Check for already clocked in on this job
	if ( $mysqli->query("SELECT `in` FROM `inout` WHERE `job` = '$job' AND `in` > 0 AND `out` = 0;")->num_rows ) {
		error("Already clocked in for this job at " . date("m/d/Y H:i:s"));
	}
	
	//Get timestamp
	if($_POST['now']) {
		$time = time();
	} elseif ( $_POST[ 'timestamp' ] ) {
		$time = intval( $_POST[ 'timestamp' ] );
	} else {
		if( ($time = mktime(intval($_POST['h']), intval($_POST['m']), 0, intval($_POST['mo']), intval($_POST['d']), intval($_POST['y']))) < 1 ) {
			error("Date Error");
		}
	}
	//Clock In
	if($mysqli->query("INSERT INTO `inout` (`in`, `out`, `job`) VALUES ('$time', '0', '$job');")) {
		$id = $mysqli->insert_id;
	} else {
		error ("DB Clockin Error");
	}
	if(!$mysqli->query("UPDATE `data` SET `value` = '$id' WHERE `name` = 'clockedin';")) {
		error("Did not update clockedin");
	}
	
	success("Clocked In");
}

function clock_out($id = false) {
	global $mysqli;
	//Get timestamp
	if($_POST['now']) {
		$time = time();
	} elseif ( $_POST[ 'timestamp' ] ) {
		$time = intval( $_POST[ 'timestamp' ] );
	} else {
		if( ($time = mktime(intval($_POST['h']), intval($_POST['m']), 0, intval($_POST['mo']), intval($_POST['d']), intval($_POST['y']))) < 1 ) {
			error("Date Error");
		}
	}
	
	if ( $_POST['job'] ) {
		$result = $mysqli->query("SELECT `punch_id` FROM `inout` WHERE `in` > 0 AND `out` = 0 AND `job` = '" . $mysqli->real_escape_string($_POST['job']) . "';");
		if ( $result->num_rows > 0 ) {
			$id = $result->fetch_object()->punch_id;
		} else {
			error("Not currently clocked in for that job");
		}
	} elseif(!$id) {
		$id = $mysqli->query("SELECT `value` FROM `data` WHERE `name` = 'clockedin';")->fetch_object()->value;
	}
	$id = intval($id);
	
	if($mysqli->query("UPDATE `inout` SET `out` = '$time' WHERE `punch_id` = '$id';")) {
		if(!$mysqli->query("UPDATE `data` SET `value` = '0' WHERE `name` = 'clockedin';"))
			error("Did not update clockedin");
	} else {
		error ("DB Clockout Error");
	}
	
	success("Clocked Out&co_id=$id");
}