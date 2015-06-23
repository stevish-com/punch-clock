<?php
/*
This file is for returning how long the user has been clocked in for.
(Used in an ajax cal from index.php)


    Copyright (C) 2014 Stephen Narwold
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/
include_once("db_config.php");
require_once("functions.php");
if(!$sql_user || !$sql_pass || !$sql_db) {
	die("Set up the variables in db_config.php (you may copy it from db_config_example.php)");
}
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


?>