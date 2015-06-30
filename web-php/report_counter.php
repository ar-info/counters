<?php 

include("/home/u17160/raevsky.com/counters_db_params.inc");

$mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
if ($mysqli->connect_errno) {
    echo "Error connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	http_response_code(500);
	exit;
}
echo $mysqli->host_info . "<br>";



echo "Hello, World!<br>"; 

 
echo 'Parameters: ' . htmlspecialchars($_GET["time"]) . ' ' . htmlspecialchars($_GET["counter"]) . ' ' . htmlspecialchars($_GET["value"]);

if ()

?>
