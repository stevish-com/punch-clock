<?php
/*
Stevish Punch Clock in PHP

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
session_start();

//Rebuild Post from Session
$rebuildable = array(
	'report_job',
	'from_date',
	'to_date'
	);
if ( $_POST['action'] != 'update_report' ) {
	foreach( $rebuildable as $key ) {
		$_POST[ $key ] = $_SESSION[ 'post-' . $key ];
	}
} else {
	foreach( $rebuildable as $key ) {
		$_SESSION[ 'post-' . $key ] = $_POST[ $key ];
	}
}

include_once("db_config.php");
$now = time();
if(!$sql_user || !$sql_pass || !$sql_db) {
	die("Set up the variables in db_config.php (you may copy it from db_config_example.php)");
}
$mysqli = new mysqli('localhost', $sql_user, $sql_pass, $sql_db);
if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

$status = array(0 => 'unpaid', 1 => 'invoiced', 2=> 'paid');
$pass_hash = $mysqli->query("SELECT `value` FROM `data` WHERE `name` = 'pass';")->fetch_object()->value;
$thisjob = intval($_POST['report_job']);
$thisrate = $mysqli->query("SELECT `rate` FROM `jobs` WHERE `id` = '$thisjob';")->fetch_object()->rate;
$from_date = preg_replace("/[^0-9\/]/", "", $_POST['from_date']);
$to_date = preg_replace("/[^0-9\/]/", "", $_POST['to_date']);

//Save Password
if ( $_POST[ 'password' ] ) {
	setrawcookie( 'pass', s_hash( $_POST[ 'password' ] ), time() + 60*60*24*30 );
}

if ( isset( $from_date ) ) {
	$_SESSION['from_date'] = $from_date;
}
if ( isset( $to_date ) ) {
	$_SESSION['to_date'] = $to_date;
}

//Get Jobs
$jobs_result = $mysqli->query("SELECT * FROM `jobs` left join `inout` on `jobs`.`id` = `inout`.`job` WHERE `jobs`.`inactive` != 1 GROUP BY `inout`.`job` ORDER BY max(`inout`.`in`) DESC;");
$job_options = '';
while($job = $jobs_result->fetch_assoc()) {
	if($job['id'] == $thisjob) {
		$job_options .= "<option value='{$job['id']}' selected='selected'>{$job['name']}</option>\n";
	} else {
		$job_options .= "<option value='{$job['id']}'>{$job['name']}</option>\n";
	}
}

if($clockedin = $mysqli->query("SELECT `value` FROM `data` WHERE `name` = 'clockedin';")->fetch_object()->value) {
	$clockedintime = date('H:i:s m/d/Y', $clockedintimestamp = $mysqli->query("SELECT `in` FROM `inout` WHERE `punch_id` = '$clockedin';")->fetch_object()->in);
}
if (intval($_GET['co_id'])) {
	$punch_info = $mysqli->query("SELECT * FROM `inout` WHERE `punch_id` = '" . intval($_GET['co_id']) . "';")->fetch_assoc();
}

switch($_POST['action']) {
	case 'get_time_in':
		if ($clockedin) {
			die( /*h2hm( ( $now - $clockedintimestamp ) / 3600)*/ );
		} else {
			die("0");
		}
		break;
	case 'in':
		if ( s_hash( $_POST[ 'password' ] ) != $pass_hash && $_COOKIE[ 'pass' ] != $pass_hash ) {
			die("Bad password. Go back and try again");
		}
		if(!$clockedin) {
			clock_in();
		} else {
			error("Already clocked in...");
		}
		break;
	case 'out':
		if ( s_hash( $_POST[ 'password' ] ) != $pass_hash && $_COOKIE[ 'pass' ] != $pass_hash ) {
			die("Bad password. Go back and try again");
		}
		if($clockedin) {
			clock_out($clockedin);
		} else {
			error("Already clocked out...");
		}
		break;
	case "edit_status":
		$punch_id = intval( $_POST[ 'punch_id' ] );
		$paid = array_search( $_POST[ 'status' ], $status );
		if ( $result = $mysqli->query("SELECT * FROM `inout` WHERE `punch_id` = '$punch_id';") && $paid !== false ) {
			if ( $mysqli->query("UPDATE `inout` SET `paid` = '$paid' WHERE `punch_id` = '$punch_id';") ) {
				echo "Enty Updated<br/><br/>";
			} else {
				echo "Enty Not Updated<br/><br/>";
			}
		} else {
			echo "Nope<br/>";
		}
		break;
}

if($clockedin) {
	echo "Clocked in at $clockedintime<br/><br/>";
}
?>
<head>
	<title>Punch Clock</title>
	<?php if ( stripos( $_SERVER[ 'HTTP_USER_AGENT' ], 'android' ) ) { ?>
		<link rel="stylesheet" type="text/css" href="mobile.css">
	<?php } ?>
</head>
<!--<script type="text/javascript">
	var xmlhttp=new XMLHttpRequest();
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
			document.getElementById("time_in").innerHTML=xmlhttp.responseText;
		}
	}
	window.setInterval('refreshTimeIn()', 5000);
	function refreshTimeIn() {
		xmlhttp.open("POST","index.php",true);
		xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
		xmlhttp.send("action=get_time_in");
	}

</script>-->

<form method="POST" id="punchform">
	<span id="time_input" class="formline">Time: <input type="text" size="2" value="<?php echo date("H", $now); ?>" name="h" />:<input type="text" size="2" value="<?php echo date("i", $now); ?>" name="m" /></span> <span id="date_input" class="formline">(<input type="text" size="2" value="<?php echo date("m", $now); ?>" name="mo" />/<input type="text" size="2" value="<?php echo date("d", $now); ?>" name="d" />/<input type="text" size="4" class="digit4" value="<?php echo date("Y", $now); ?>" name="y" />)<br/></span>
	<?php if($clockedin) {
		$jobin = $mysqli->query("SELECT `name` FROM `jobs` WHERE `id` = '" . $mysqli->query("SELECT `job` FROM `inout` WHERE `punch_id` = '$clockedin';")->fetch_object()->job . "';")->fetch_object()->name;
		echo "Clocked in for $jobin (<span id='time_in' onclick='refreshTimeIn()'>" . h2hm(($now - $clockedintimestamp) / 3600) . "</span>)<br/>";
	} else { ?>
		Job: <select name="job">
			<?php
				echo $job_options;
			?>
			<option value="0">Add New...</option>
		</select> <span id="newjob_input"><input type="text" class="text" name="newjob" />  $<input type="text" class="newrate" name="newrate" size="3" /><br/></span>
	<?php } ?>
	<input type="hidden" name="action" value="<?php echo $clockedin ? 'out' : 'in'; ?>" />
	<table>
	<tr id="username_row"><td><label for="username">User: </label></td><td><input type="text" name="username" /></td></tr>
	<?php if ( $pass_hash == $_COOKIE[ 'pass' ] ) {
		echo "<tr id='pass_row_good'><td>You're good.</td><td></td></tr>";
	} else {
		echo '<tr id="pass_row"><td><label for="password">Pass: </label></td><td><input type="password" name="password" /></td></tr>';
	} ?>
	</table>
	<input type="submit" class="submit" value="<?php echo $clockedin ? 'Clock Out' : 'Clock In'; ?>" />
	<input type="submit" class="submit" name="now" value="<?php echo $clockedin ? 'Clock Out NOW' : 'Clock In NOW'; ?>" />
</form><br/>
<?php
if (isset($punch_info)) {
	$job_info = $mysqli->query("SELECT `rate`, `name` FROM `jobs` WHERE `id` = '{$punch_info['job']}';")->fetch_object();
	$hours = ($punch_info['out'] - $punch_info['in']) / 3600;
	$earned = $hours * $job_info->rate;
	$hours = h2hm($hours);
	echo "<strong>Punch for {$job_info->name}</strong>: " . date("m/d/Y H:i:s", $punch_info['in']) . ' - ' . date("m/d/Y H:i:s", $punch_info['out']) . " = $hours @ {$job_info->rate}/hour = $" . number_format($earned, 2);
} else {
	echo "<br/>";
} 
?>
<hr/>
<form method="POST" id="reportform">
	View Job Data: <select name="report_job">
		<?php
			echo $job_options;
		?>
	</select>
	From <input type="text" name="from_date" value="<?php echo $_SESSION['from_date']; ?>" />
	To <input type="text" name="to_date" value="<?php echo $_SESSION['to_date']; ?>" />
	<input type="hidden" name="action" value="update_report" />
	<input type="submit" value="Submit" />
</form>

<?php
do_report();

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
		error("Job Error");
	}
	
	//Get timestamp
	if($_POST['now']) {
		$time = time();
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

function clock_out($id) {
	global $mysqli;
	//Get timestamp
	if($_POST['now']) {
		$time = time();
	} else {
		if( ($time = mktime(intval($_POST['h']), intval($_POST['m']), 0, intval($_POST['mo']), intval($_POST['d']), intval($_POST['y']))) < 1 ) {
			error("Date Error");
		}
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

function do_report() {
	global $thisjob, $from_date, $to_date, $mysqli, $status, $thisrate;
	if ( $thisjob ) {
		$where = "WHERE `job` = '$thisjob' AND `in` > '0' AND `out` > '0'";
		if($from_date) {
			$fds = explode("/", $from_date);
			$from_date_unix = mktime(0,0,0,$fds[0],$fds[1],$fds[2]);
			$where .= " AND `in` >= '$from_date_unix'";
		}
		if($to_date) {
			$tds = explode("/", $to_date);
			$to_date_unix = mktime(23,59,59,$tds[0],$tds[1],$tds[2]);
			$where .= " AND `in` <= '$to_date_unix'";
		}
		$result = $mysqli->query("SELECT * FROM `inout` $where ORDER BY `in` ASC");
		$jobdata = array();
		$paidhours = $paidearned = $unpaidhours = $unpaidearned = $invoicedhours = $invoicedearned = 0;
		while($inout = $result->fetch_assoc()) {
			$seconds = $inout['out'] - $inout['in'];
			$hours = $seconds / 3600;
			$inout['hours'] = h2hm($hours);
			$inout['earned'] = $hours * $thisrate;
			
			$statushours = $status[$inout['paid']] . 'hours';
			$statusearned = $status[$inout['paid']] . 'earned';
			
			$jobdata[$status[$inout['paid']]][] = $inout;
			$$statushours += $hours;
			$$statusearned += $inout['earned'];
		}

		foreach($status as $s) {
			$shours = $s . 'hours';
			$searned = $s . 'earned';
			if(count($jobdata[$s]) > 0) {
				?>
				<table class="report_table" border="1" cellspacing="2" cellpadding="5">
					<tr><th colspan="4"><?php echo strtoupper($s); ?></th></tr>
					<tr><th>In</th><th>Out</th><th>Hours</th><th>Earned</th><th>Change to...</th></tr>
					<?php
						foreach( $jobdata[$s] as $inout ) {
							echo '<tr><td>' . date("m/d/Y H:i:s", $inout['in']) . '</td><td>' . date("m/d/Y H:i:s", $inout['out']) . '</td><td>' . $inout['hours'] . '</td><td>' . $inout['earned'] . '</td>';
							echo '<td><form method="POST"><input type="hidden" name="action" value="edit_status" /><input type="hidden" name="punch_id" value="' . $inout['punch_id'] . '" />';
							if ( 'unpaid' != $s ) {
								echo '<input type="submit" name="status" value="unpaid" />';
							}
							if ( 'invoiced' != $s ) {
								echo '<input type="submit" name="status" value="invoiced" />';
							}
							if ( 'paid' != $s ) {
								echo '<input type="submit" name="status" value="paid" />';
							}
							echo '</form></td></tr>';
						}
						echo '<tr><td></td><th>Totals</th><th>' . h2hm($$shours) . '</th><th>$' . number_format($$searned, 2) . '</th></tr>';
					?>
				</table>
				<?php
			}
		}
	}
}

function error($error) {
	header("Location: http://s.stevish.com/punch/?error=$error");
	die();
}

function success($message) {
	header("Location: http://s.stevish.com/punch/?success=$message");
	die();
}

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

