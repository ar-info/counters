<?php 

include("/home/u17160/raevsky.com/counters_db_params.inc");

$mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
if ($mysqli->connect_errno) {
    echo "Error connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	http_response_code(500);
	exit;
}
echo $mysqli->host_info . "<br>";


 
$req_time = htmlspecialchars($_GET["time"]);
$req_counter = htmlspecialchars($_GET["counter"]);
$req_value = htmlspecialchars($_GET["value"]);
 
echo 'Parameters: ' . $req_time . ' ' . $req_counter . ' ' . $req_value;

#http_response_code(500);
#exit;

if (!strcasecmp(htmlspecialchars($_GET["counter"]), "H")){
	
	$insert_str = "INSERT INTO `COUNTER_HOT`(`TIME`, `COUNT`) VALUES (\"" . gmdate("Y-m-d H:i:s", $req_time) . "\"," . $req_value . ")";
	echo $insert_str . "<br>";
		
} elseif (!strcasecmp(htmlspecialchars($_GET["counter"]), "C")){
	
	$insert_str = "INSERT INTO `COUNTER_COLD`(`TIME`, `COUNT`) VALUES (\"" . gmdate("Y-m-d H:i:s", $req_time) . "\"," . $req_value . ")";
	echo $insert_str . "<br>";

} else {
	
	echo "Invalid parameter " . htmlspecialchars($_GET["counter"]);
	http_response_code(500);
	exit;
	
}

if (!$mysqli->query($insert_str)) {
	echo "Error insert: (" . $mysqli->errno . ") " . $mysqli->error . "<br>";
	echo "Query string: " . $insert_str . "<br>";
	http_response_code(500);
}

?>
