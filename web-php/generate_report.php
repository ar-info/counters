<?php 

if (!ini_get('display_errors')) {
    ini_set('display_errors', '1');
}

require 'PHPMailerAutoload.php';

include("/home/u17160/raevsky.com/counters_db_params.inc");


$template_path = "/home/u17160/raevsky.com/www/counters/template.rtf";
$report_path = "/home/u17160/raevsky.com/www/counters/report.rtf";


$re_reportdate = "/(%REPORTDATE%)/i"; 
$re_prevreportdate = "/(%PREVREPORTDATE%)/i"; 

$re_ccurvalue = "/(%CCURVALUE%)/i"; 
$re_hcurvalue = "/(%HCURVALUE%)/i"; 

$re_cprevvalue = "/(%CPREVVALUE%)/i"; 
$re_hprevvalue = "/(%HPREVVALUE%)/i"; 

$re_cconsump = "/(%CCONSUMP%)/i"; 
$re_hconsump = "/(%HCONSUMP%)/i"; 


function get_parameter($mysqli, $parameter_name)
{

	$request_str = "SELECT `VALUE` FROM `PROPERTIES` WHERE NAME = '" . $parameter_name . "'";
	$result = $mysqli->query($request_str);
	if (!$result) {
		echo "Error get parameter: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
		echo "Query string: " . $request_str . "<br>";
		return 0;
	}
	$ret_array = mysqli_fetch_array($result, MYSQL_ASSOC);
	$ret_value = $ret_array["VALUE"];
	$result->free();
	
	return $ret_value;
}


$mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
if ($mysqli->connect_errno) {
    echo "Error connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	exit;
}


$report_date = get_parameter($mysqli, "REPORT_DATE");
$today_date = date("d");

if ((int)$report_date != (int)$today_date){
	exit;
}

$reportdate = date("d.m.Y");



$request_str = "SELECT max(TIME) FROM `VERIFICATION`";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}

$last_time_array = mysqli_fetch_array($result, MYSQL_NUM);
$last_verification = $last_time_array[0];
printf("Last verification = %s<br>", $last_verification);
$result->free();

$request_str = "SELECT * FROM `VERIFICATION` WHERE TIME='" . $last_verification . "'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$counters = mysqli_fetch_array($result, MYSQL_ASSOC);
$c_verification_value = $counters["COLD_VALUE"];
$h_verification_value = $counters["HOT_VALUE"];
printf("Cold counter verification = %f<br>", $c_verification_value);
printf("Hot counter verification = %f<br><br>", $h_verification_value);
$result->free();


$request_str = "SELECT max(TIME) FROM `REPORTS`";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}

$last_time_array = mysqli_fetch_array($result, MYSQL_NUM);
$last_report = $last_time_array[0];
$prevreportdate = date("d.m.Y", strtotime($last_time_array[0]));
printf("Last report = %s<br>", $prevreportdate);
$result->free();

$request_str = "SELECT * FROM `REPORTS` WHERE TIME='" . $last_report . "'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$counters = mysqli_fetch_array($result, MYSQL_ASSOC);
$c_report_value = $counters["COLD_VALUE"];
$h_report_value = $counters["HOT_VALUE"];
printf("Cold counter reported = %f<br>", $c_report_value);
printf("Hot counter reported = %f<br><br>", $h_report_value);
$result->free();

$cold_multiplier = get_parameter($mysqli, "COLD_MULTIPLIER");
$hot_multiplier = get_parameter($mysqli, "HOT_MULTIPLIER");

$request_str = "SELECT SUM(COUNT) FROM `COUNTER_COLD` WHERE TIME > '" . $last_verification . "'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$cold_counter_array = mysqli_fetch_array($result, MYSQL_NUM);
$cold_counter_ticks = $cold_counter_array[0];
printf("<br>Cold counter ticks = %f<br>", $cold_counter_ticks);
$result->free();

$request_str = "SELECT SUM(COUNT) FROM `COUNTER_HOT` WHERE TIME > '" . $last_verification . "'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$hot_counter_array = mysqli_fetch_array($result, MYSQL_NUM);
$hot_counter_ticks = $hot_counter_array[0];
printf("Hot counter ticks = %f<br>", $hot_counter_ticks);
$result->free();

$cold_counter = $c_verification_value + 10.0 * $cold_counter_ticks * $cold_multiplier;
$hot_counter = $h_verification_value + 10.0 * $hot_counter_ticks * $hot_multiplier;

$ccurvalue = strval(round($cold_counter));
$hcurvalue = strval(round($hot_counter));

$cprevvalue = strval($c_report_value);
$hprevvalue = strval($h_report_value);

$cconsump = strval(round($cold_counter) - $c_report_value);
$hconsump = strval(round($hot_counter) - $h_report_value);



$buffer = file_get_contents($template_path);

// parse by segments

$buffer = preg_replace($re_reportdate, $reportdate, $buffer);
$buffer = preg_replace($re_prevreportdate, $prevreportdate, $buffer);

$buffer = preg_replace($re_ccurvalue, $ccurvalue, $buffer);
$buffer = preg_replace($re_hcurvalue, $hcurvalue, $buffer);

$buffer = preg_replace($re_cprevvalue, $cprevvalue, $buffer);
$buffer = preg_replace($re_hprevvalue, $hprevvalue, $buffer);

$buffer = preg_replace($re_cconsump, $cconsump, $buffer);
$buffer = preg_replace($re_hconsump, $hconsump, $buffer);


//printf("%s\n", $buffer);

file_put_contents($report_path, $buffer);

$mail = new PHPMailer;
$mail->setFrom('counters@raevsky.com', 'Water Counters');
$mail->Subject = 'Water counters report';
$mail->msgHTML("See attached file");
$mail->addAttachment($report_path, 'report.rtf');

$addr_list = get_parameter($mysqli, "EMAIL_LIST");      
	  
foreach ($mail->parseAddresses($addr_list) as $address) {
    $mail->addAddress($address['address'], $address['name']);
	printf("%s %s<br>", $address['address'], $address['name']);
}
	  
if (!$mail->send()) {
    $msg = "Mailer Error: " . $mail->ErrorInfo;
	printf("%s\n", $msg);
} else {
    $msg = "Message sent!";
}
 
 
$request_str = "INSERT INTO `REPORTS`(`TIME`, `HOT_VALUE`, `COLD_VALUE`) VALUES (NOW()," . $hcurvalue . "," . $ccurvalue . ")";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}


?>