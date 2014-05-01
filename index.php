<?php
session_start();
$now = time();
$sql_user = 'stevish_punch';
$sql_db = 'stevish_punch';
$sql_pass = '7DZ)^%8Q,FOb';
$mysqli = new mysqli('localhost', $sql_user, $sql_pass, $sql_db);
if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}


$thisjob = intval($_POST['job']);
$thisrate = $mysqli->query("SELECT `rate` FROM `jobs` WHERE `id` = '$thisjob';")->fetch_object()->rate;
$from_date = preg_replace("/[^0-9\/]/", "", $_POST['from_date']);
$to_date = preg_replace("/[^0-9\/]/", "", $_POST['to_date']);

if($from_date) {
	$_SESSION['from_date'] = $from_date;
}
if($to_date) {
	$_SESSION['to_date'] = $to_date;
}

//Get Jobs
$jobs_result = $mysqli->query("SELECT * FROM `jobs` left join `inout` on `jobs`.`id` = `inout`.`job` WHERE `jobs`.`inactive` != 1 GROUP BY `inout`.`job` ORDER BY max(`inout`.`in`) DESC;");


if($clockedin = $mysqli->query("SELECT `value` FROM `data` WHERE `name` = 'clockedin';")->fetch_object()->value) {
	$clockedintime = date('H:i:s m/d/Y', $clockedintimestamp = $mysqli->query("SELECT `in` FROM `inout` WHERE `punch_id` = '$clockedin';")->fetch_object()->in);
}
if (intval($_GET['co_id'])) {
	$punch_info = $mysqli->query("SELECT * FROM `inout` WHERE `punch_id` = '" . intval($_GET['co_id']) . "';")->fetch_assoc();
}

switch($_POST['action']) {
	case 'get_time_in':
		if ($clockedin) {
			die( h2hm(($now - $clockedintimestamp) / 3600) );
		} else {
			die("0");
		}
		break;
	case 'in':
		if(!$clockedin) {
			clock_in();
		} else {
			error("clockedinout did not match");
		}
		break;
	case 'out':
		if($clockedin) {
			clock_out($clockedin);
		} else {
			error("clockedinout did not match");
		}
		break;
	case 'update_report':
		$status = array(0 => 'unpaid', 1 => 'invoiced', 2=> 'paid');

		if($thisjob) {
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
		}
		
		ob_start();
		foreach($status as $s) {
			$shours = $s . 'hours';
			$searned = $s . 'earned';
			if(count($jobdata[$s]) > 0) {
				?>
				<table border="1" cellspacing="2" cellpadding="5">
					<tr><th colspan="4"><?php echo strtoupper($s); ?></th></tr>
					<tr><th>In</th><th>Out</th><th>Hours</th><th>Earned</th></tr>
					<?php
						foreach($jobdata[$s] as $inout)
							echo '<tr><td>' . date("m/d/Y H:i:s", $inout['in']) . '</td><td>' . date("m/d/Y H:i:s", $inout['out']) . '</td><td>' . $inout['hours'] . '</td><td>' . $inout['earned'] . '</td></tr>';
						echo '<tr><td></td><th>Totals</th><th>' . h2hm($$shours) . '</th><th>$' . number_format($$searned, 2) . '</th></tr>';
					?>
				</table>
				<?php
			}
		}
		$_SESSION['report'] = ob_get_contents();
		$_SESSION['report_job_options'] = '';
		while($job = $jobs_result->fetch_assoc()) {
			if($job['id'] == $thisjob) {
				$_SESSION['report_job_options'] .= "<option value='{$job['id']}' selected='selected'>{$job['name']}</option>\n";
			} else {
				$_SESSION['report_job_options'] .= "<option value='{$job['id']}'>{$job['name']}</option>\n";
			}
		}
		ob_end_clean();
		
		break;
}

if($_POST['action'] != 'update_report') {
	$_SESSION['job_options'] = '';
	while($job = $jobs_result->fetch_assoc()) {
		if($job['id'] == $thisjob) {
			$_SESSION['job_options'] .= "<option value='{$job['id']}' selected='selected'>{$job['name']}</option>\n";
		} else {
			$_SESSION['job_options'] .= "<option value='{$job['id']}'>{$job['name']}</option>\n";
		}
	}
	if(!$_SESSION['report_job_options']) {
		$_SESSION['report_job_options'] = $_SESSION['job_options'];
	}
}

if($clockedin) {
	echo "Clocked in at $clockedintime<br/><br/>";
}
?>
<script type="text/javascript">
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

</script>
<form method="POST">
	Time: <input type="text" size="2" value="<?php echo date("H", $now); ?>" name="h" />:<input type="text" size="2" value="<?php echo date("i", $now); ?>" name="m" /> (<input type="text" size="2" value="<?php echo date("m", $now); ?>" name="mo" />/<input type="text" size="2" value="<?php echo date("d", $now); ?>" name="d" />/<input type="text" size="4" value="<?php echo date("Y", $now); ?>" name="y" />)<br/>
	<?php if($clockedin) {
		$jobin = $mysqli->query("SELECT `name` FROM `jobs` WHERE `id` = '" . $mysqli->query("SELECT `job` FROM `inout` WHERE `punch_id` = '$clockedin';")->fetch_object()->job . "';")->fetch_object()->name;
		echo "Clocked in for $jobin (<span id='time_in' onclick='refreshTimeIn()'>" . h2hm(($now - $clockedintimestamp) / 3600) . "</span>)<br/>";
	} else { ?>
		Job: <select name="job">
			<?php
				echo $_SESSION['job_options'];
			?>
			<option value="0">Add New...</option>
		</select> <input type="text" name="newjob" />  $<input type="text" name="newrate" size="3" /><br/>
	<?php } ?>
	<input type="hidden" name="action" value="<?php echo $clockedin ? 'out' : 'in'; ?>" />
	<input type="submit" value="<?php echo $clockedin ? 'Clock Out' : 'Clock In'; ?>" />
	<input type="submit" name="now" value="<?php echo $clockedin ? 'Clock Out NOW' : 'Clock In NOW'; ?>" />
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
<form method="POST">
	View Job Data: <select name="job">
		<?php
			echo $_SESSION['report_job_options'];
		?>
	</select>
	From <input type="text" name="from_date" value="<?php echo $_SESSION['from_date']; ?>" />
	To <input type="text" name="to_date" value="<?php echo $_SESSION['to_date']; ?>" />
	<input type="hidden" name="action" value="update_report" />
	<input type="submit" value="Submit" />
</form>

<?php
if($_SESSION['report']) {
	echo $_SESSION['report'];
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

function error($error) {
	header("Location: http://s.stevish.com/punch/?error=$error");
	die();
}

function success($message) {
	header("Location: http://s.stevish.com/punch/?success=$message");
	die();
}

function h2hm($hours) {
	$minutes = round(($hours - floor($hours)) * 60);
	return floor($hours) . ':' . str_pad($minutes, 2, "0", STR_PAD_LEFT);
}
?>