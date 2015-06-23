<?php
include_once("db_config.php");
require_once("functions.php");
require_once("stevishSecComm.php");
if(!$sql_user || !$sql_pass || !$sql_db) {
	die("Set up the variables in db_config.php (you may copy it from db_config_example.php)");
}
$mysqli = new mysqli('localhost', $sql_user, $sql_pass, $sql_db);
if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

if ( !$ssc_key || !$ssc_iv ) {
	die('ERROR#You must define $ssc_key and $ssc_iv to use the android api');
}

$ssc = new StevishSecComm($ssc_key, $ssc_iv);

//Check password
$goodpass = false;
if ( $_GET['pass'] ) {
	$pass_hash = $mysqli->query("SELECT `value` FROM `data` WHERE `name` = 'pass';")->fetch_object()->value;
	if ( ($pass = $ssc->decrypt_time($_GET['pass'], 60)) !== false ) {
		if ( s_hash($pass) == $pass_hash ) {
			$goodpass = true;
		}
	} else {
		die("ERROR#Time mismatch. Off by more than a minute: " . $GLOBALS['td']);
	}
}

switch( $_GET['action'] ) {
	case "getjobs":
		$jobstring = "JOBS#";
		$job_result = $mysqli->query("SELECT * FROM `jobs` left join `inout` on `jobs`.`id` = `inout`.`job` WHERE `jobs`.`inactive` != 1 GROUP BY `inout`.`job` ORDER BY max(`inout`.`in`) DESC;");
		while( $jobname = $job_result->fetch_array() ) {
			$jobstring .= $jobname['name'] . ",";
		}
		die( $jobstring );
		break;
	case "getclockins":
		$outstring = "CLOCK_INS#";
		$result = $mysqli->query("SELECT `inout`.`in` AS `in`, `jobs`.`name` as `job` FROM `inout` LEFT JOIN `jobs` ON `inout`.`job`=`jobs`.`id` WHERE `inout`.`out` = 0 AND `inout`.`in` > 0");
		while( $ci = $result->fetch_assoc() ) {
			$outstring .= $ci['job'] . ',' . $ci['in'] . ';';
		}
		die($outstring);
		break;
}


if ( $_GET['punch'] ) {
	if ( !$goodpass ) {
		die("ERROR#Bad Password");
	}
	$punch = explode(',', $_GET['punch']);
	$punch[2] = $mysqli->real_escape_string($punch[2]);
	$_POST['job'] = $mysqli->query("SELECT `id` FROM `jobs` WHERE `name` = '{$punch[2]}';")->fetch_object()->id;
	$_POST['timestamp'] = intval($punch[1]);
	if ( "in" == $punch[0] ) {
		clock_in();
	} else {
		clock_out();
	}
	die("PUNCH_SUCCESS#Punch synced successfully");
}

function error($msg) {
	if($_GET['punch']) {
		die("PUNCH_ERROR#" . $msg);
	}
	die("ERROR#" . $msg);
}

function success($msg) {
	global $mysqli;
	if ( stristr($msg, '&') !== false ) {
		$params = explode( "&", $msg );
		foreach( $params as $k => $v ) {
			if ( $k == 0 ) {
				$msg = $v;
			} else {
				$param = explode("=", $v, 2);
				$newparams[ $param[0] ] = $param[1];
			}
		}
		if ( $punch_info = $mysqli->query("SELECT * FROM `inout` WHERE punch_id = '{$newparams['co_id']}';")->fetch_assoc() ) {
			$job_info = $mysqli->query("SELECT `rate`, `name` FROM `jobs` WHERE `id` = '{$punch_info['job']}';")->fetch_object();
			$hours = ($punch_info['out'] - $punch_info['in']) / 3600;
			$earned = $hours * $job_info->rate;
			$hours = h2hm($hours);
			$msg .= " $hours @ {$job_info->rate} = $" . number_format($earned, 2);
			die("PUNCH_SUCCESS#" . $msg);
		}
	}
	die("PUNCH_SUCCESS#" . $msg);
}