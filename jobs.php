<?php
$sql_user = 'stevish_punch';
$sql_db = 'stevish_punch';
$sql_pass = '7DZ)^%8Q,FOb';
$mysqli = new mysqli('localhost', $sql_user, $sql_pass, $sql_db);
if (mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

//VarSec
$thisjob = intval($_POST['job']);
$from_date = preg_replace("/[^0-9\/]/", "", $_POST['from_date']);
$to_date = preg_replace("/[^0-9\/]/", "", $_POST['to_date']);


$jobs_result = $mysqli->query("SELECT * FROM `jobs` left join `inout` on `jobs`.`id` = `inout`.`job` WHERE `jobs`.`inactive` != 1 GROUP BY `inout`.`job` ORDER BY max(`inout`.`in`) DESC;");
$job_options = '';
while($job = $jobs_result->fetch_assoc()) {
	if($job['id'] == $thisjob) {
		$thisrate = $job['rate'];
		$job_options .= "<option value='{$job['id']}' selected='selected'>{$job['name']}</option>\n";
	} else {
		$job_options .= "<option value='{$job['id']}'>{$job['name']}</option>\n";
	}
}



?>
<form method="POST">
	Job: <select name="job">
		<?php
			echo $job_options;
		?>
	</select><br/>
	From (mm/dd/yyyy) <input type="text" name="from_date" value="<?php echo $from_date; ?>" /><br/>
	To (mm/dd/yyyy) <input type="text" name="to_date" value="<?php echo $to_date; ?>" /><br/>
	<input type="submit" value="Submit" />
</form><br/>
<a href="/punch/">Clock in/out</a><br/><br/>
<?php



$status = array(0 => 'unpaid', 1 => 'invoiced', 2=> 'paid');

if($thisjob) {
	$where = "WHERE `job` = '$thisjob'";
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

?>
<a href="/punch/">Clock in/out</a>

<?php
function h2hm($hours) {
	$minutes = round(($hours - floor($hours)) * 60);
	return floor($hours) . ':' . str_pad($minutes, 2, "0", STR_PAD_LEFT);
}
?>