<?php 

include("/home/u17160/raevsky.com/counters_db_params.inc");

if (!ini_get('display_errors')) {
    ini_set('display_errors', '1');
}

$mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
if ($mysqli->connect_errno) {
    echo "Error connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	exit;
}

$request_str = "SELECT max(TIME) FROM `VERIFICATION`";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}

$last_time_array = mysqli_fetch_array($result, MYSQL_NUM);
$last_time = $last_time_array[0];
printf("Last time = %s<br>", $last_time);
$result->free();

$request_str = "SELECT * FROM `VERIFICATION` WHERE TIME='" . $last_time . "'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$counters = mysqli_fetch_array($result, MYSQL_ASSOC);
$cold_counter_start_value = $counters["COLD_VALUE"];
$hot_counter_start_value = $counters["HOT_VALUE"];
printf("Cold counter = %f<br>", $cold_counter_start_value);
printf("Hot counter = %f<br><br>", $hot_counter_start_value);
$result->free();

$request_str = "SELECT `VALUE` FROM `PROPERTIES` WHERE NAME = 'HOT_MULTIPLIER'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$hot_multiplier = mysqli_fetch_array($result, MYSQL_ASSOC);
printf("Hot multiplier = %f<br>", $hot_multiplier["VALUE"]);
$result->free();

$request_str = "SELECT `VALUE` FROM `PROPERTIES` WHERE NAME = 'COLD_MULTIPLIER'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$cold_multiplier = mysqli_fetch_array($result, MYSQL_ASSOC);
printf("Cold multiplier = %f<br><br>", $cold_multiplier["VALUE"]);
$result->free();

$request_str = "SELECT `VALUE` FROM `PROPERTIES` WHERE NAME = 'HOT_LIMIT'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$hot_limit = mysqli_fetch_array($result, MYSQL_ASSOC);
printf("Hot limit = %f<br>", $hot_limit["VALUE"]);
$result->free();

$request_str = "SELECT `VALUE` FROM `PROPERTIES` WHERE NAME = 'COLD_LIMIT'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$cold_limit = mysqli_fetch_array($result, MYSQL_ASSOC);
printf("Cold limit = %f<br><br>", $cold_limit["VALUE"]);
$result->free();

$request_str = "SELECT SUM(COUNT) FROM `COUNTER_COLD` WHERE TIME > '" . $last_time . "'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$cold_counter_array = mysqli_fetch_array($result, MYSQL_NUM);
$cold_counter_ticks = $cold_counter_array[0];
printf("<br>Cold counter ticks = %f<br>", $cold_counter_ticks);
$result->free();

$request_str = "SELECT SUM(COUNT) FROM `COUNTER_HOT` WHERE TIME > '" . $last_time . "'";
$result = $mysqli->query($request_str);
if (!$result) {
	echo "Error define last date: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $request_str . "<br>";
}
$hot_counter_array = mysqli_fetch_array($result, MYSQL_NUM);
$hot_counter_ticks = $hot_counter_array[0];
printf("Hot counter ticks = %f<br>", $hot_counter_ticks);
$result->free();

$cold_counter = $cold_counter_start_value + 10.0 * $cold_counter_ticks * $cold_multiplier["VALUE"];
$hot_counter = $hot_counter_start_value + 10.0 * $hot_counter_ticks * $hot_multiplier["VALUE"];

printf("<br><br><b>Cold counter: %f<br>Hot counter: %f</b><br>", $cold_counter, $hot_counter);

mysqli_close($mysqli);
?>
