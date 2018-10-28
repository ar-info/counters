<?php 

include("/home/u17160/raevsky.com/counters_db_params.inc");

if (!function_exists('http_response_code'))
{
    function http_response_code($newcode = NULL)
    {
        static $code = 200;
        if($newcode !== NULL)
        {
            header('X-PHP-Response-Code: '.$newcode, true, $newcode);
            if(!headers_sent())
                $code = $newcode;
        }       
        return $code;
    }
}

$mysqli = new mysqli($dbHost, $dbUser, $dbPassword, $dbDatabase);
if ($mysqli->connect_errno) {
    error_log( "Error connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error );
	http_response_code(500);
	exit;
}
echo $mysqli->host_info . "<br>";


$req_time = htmlspecialchars($_GET["time"]);
$req_counter = htmlspecialchars($_GET["counter"]);
$req_value = htmlspecialchars($_GET["value"]);

 
echo 'Parameters: ' . $req_time . ' ' . $req_counter . ' ' . $req_value;

http_response_code(200);
exit;

if (!strcasecmp(htmlspecialchars($_GET["counter"]), "H")){
	
	$insert_str = "INSERT INTO `COUNTER_HOT`(`TIME`, `COUNT`) VALUES (\"" . date("Y-m-d H:i:s", $req_time) . "\"," . $req_value . ")";
	echo $insert_str . "<br>";
		
} elseif (!strcasecmp(htmlspecialchars($_GET["counter"]), "C")){
	
	$insert_str = "INSERT INTO `COUNTER_COLD`(`TIME`, `COUNT`) VALUES (\"" . date("Y-m-d H:i:s", $req_time) . "\"," . $req_value . ")";
	echo $insert_str . "<br>";

} else {
	
	error_log( "Invalid parameter " . htmlspecialchars($_GET["counter"]));
	http_response_code(500);
	exit;
	
}

if (!$mysqli->query($insert_str)) {
	error_log("Error insert: (" . $mysqli->errno . ") " . $mysqli->error . "<br>");
	error_log("Query string: " . $insert_str . "<br>");
	
	if ($mysqli->errno == 1062){
		// duplicate record
		http_response_code(200);
	} else {
		http_response_code(500);
	}
	exit;
}

?>
