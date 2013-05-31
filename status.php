<?php

require("include/mysql_connect.php");
require("include/common_sql.php");

$version = $_GET['v'];
$sigType = strtolower($_GET['sigType']);

if (empty($sigType)) {
	$sigs = explode(",", VALID_SIG);
}
else
{
	if (!strstr(VALID_SIG, $sigType)) {
		exit_with_error_code(1);
	}
	$sigs = array($sigType);
}

printf(status_all($sigs));

function exit_with_error_code($exitCode)
{
	header("HTTP/1.0 400 Bad Request (".$exitCode.")");
	echo $exitCode;
	exit($exitCode);
}

?>
