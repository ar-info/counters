<?php 

include("/home/u17160/raevsky.com/counters_db_params.inc");

echo ini_get('display_errors');

if (!ini_get('display_errors')) {
    ini_set('display_errors', '1');
}

echo ini_get('display_errors');


$mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
if ($mysqli->connect_errno) {
    echo "Error connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	http_response_code(500);
	exit;
}
echo $mysqli->host_info . "<br>";


$request_str = "SELECT max(TIME) FROM `VERIFICATION`";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
printf("Select returned %d rows\n", $result->num_rows);

$last_time = mysqli_fetch_array($result, MYSQL_NUM);
printf("Last time = %s\n", $last_time[0]);
$result->free();

$request_str = "SELECT * FROM `VERIFICATION` WHERE TIME='" . $last_time[0] . "'";
$result = $mysqli->query($request_str);
printf("result = %d\n", $result);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
printf("Select returned %d rows\n", $result->num_rows);
$counters = mysqli_fetch_array($result, MYSQL_ASSOC);
$cold_counter_start_value = $counters["COLD_VALUE"];
$hot_counter_start_value = $counters["HOT_VALUE"];
printf("Cold counter = %f\n", $cold_counter_start_value);
printf("Hot counter = %f\n", $hot_counter_start_value);
$result->free();

$request_str = "SELECT `VALUE` FROM `PROPERTIES` WHERE NAME = 'HOT_MULTIPLIER'";
$result = $mysqli->query($request_str);
printf("result = %d\n", $result);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
printf("Select returned %d rows<br>", $result->num_rows);
$hot_multiplier = mysqli_fetch_array($result, MYSQL_ASSOC);
printf("Hot multiplier = %f<br>", $hot_multiplier["VALUE"]);
$result->free();

$request_str = "SELECT `VALUE` FROM `PROPERTIES` WHERE NAME = 'COLD_MULTIPLIER'";
$result = $mysqli->query($request_str);
printf("result = %d\n", $result);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
printf("Select returned %d rows<br>", $result->num_rows);
$cold_multiplier = mysqli_fetch_array($result, MYSQL_ASSOC);
printf("Cold multiplier = %f<br>", $cold_multiplier["VALUE"]);
$result->free();

$request_str = "SELECT `VALUE` FROM `PROPERTIES` WHERE NAME = 'HOT_LIMIT'";
$result = $mysqli->query($request_str);
printf("result = %d\n", $result);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
printf("Select returned %d rows<br>", $result->num_rows);
$hot_limit = mysqli_fetch_array($result, MYSQL_ASSOC);
printf("Hot limit = %f<br>", $hot_limit["VALUE"]);
$result->free();

$request_str = "SELECT `VALUE` FROM `PROPERTIES` WHERE NAME = 'COLD_LIMIT'";
$result = $mysqli->query($request_str);
printf("result = %d\n", $result);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
printf("Select returned %d rows<br>", $result->num_rows);
$cold_limit = mysqli_fetch_array($result, MYSQL_ASSOC);
printf("Cold limit = %f<br>", $cold_limit["VALUE"]);
$result->free();




mysqli_close($mysqli);
?>
