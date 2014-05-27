<?php

require("include/constants.php");

if (strcmp(SUBMISSION_KEY, $_GET['apikey']) != 0) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

if (!fsockopen("www.google.com", 80)){
	exit_with_error_code(10);
}

$json = file_get_contents('php://input');
if (!$json) exit_with_error_code(9);

$json = json_decode($json);
$results = $json->{'results'};
if (!$results) exit_with_error_code(8);

require("include/mysql_connect.php");
require("include/common_sql.php");

$signerIds = array();

foreach ($results as $result) {
	array_push($signerIds, $result->{'signerId'});
	store_result($result);
}

echo "Successfully saved results to isthesigningserverdown.com for ".implode($signerIds, ", ");

exit;

function exit_with_error_code($exitCode)
{
	header("HTTP/1.0 400 Bad Request (".$exitCode.")");
	exit($exitCode);
}

function store_result($result)
{
	global $mysqli;
	
	$sigType = strtolower($result->{'signerId'});
	
	$statement = $mysqli->prepare("INSERT INTO ".DB_TABLE."
		SET signature=?,
		count=?,
		successes=?,
		failures=?,
		duration=?,
		size=?,
		retries=?;");
	$statement->bind_param('siiiiii',
		$sigType,
		$result->{'count'},
		$result->{'success'},
		$result->{'failure'},
		$result->{'duration'},
		$result->{'size'},
		$result->{'retry'});
	$statement->execute();
	$statement->close();
}

?>
