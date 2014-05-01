<?php
$sql_user = 'stevish_punch';
$sql_db = 'stevish_punch';
$sql_pass = '7DZ)^%8Q,FOb';
$mysqli = new mysqli('localhost', $sql_user, $sql_pass, $sql_db);
if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}
$now = time();

if($clockedin = $mysqli->query("SELECT `value` FROM `data` WHERE `name` = 'clockedin';")->fetch_object()->value) {
	$clockedintimestamp = $mysqli->query("SELECT `in` FROM `inout` WHERE `punch_id` = '$clockedin';")->fetch_object()->in;
}

if ($clockedin) {
	die( h2hm(($now - $clockedintimestamp) / 3600) );
} else {
	die("0");
}

function h2hm($hours) {
	$minutes = round(($hours - floor($hours)) * 60);
	return floor($hours) . ':' . str_pad($minutes, 2, "0", STR_PAD_LEFT);
}
?>