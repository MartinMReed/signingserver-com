<?php 

require("constants.php");

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
$mysqli->autocommit(TRUE);

$mysqlc = mysql_connect(DB_HOST, DB_USER, DB_PASSWD) or die("error connecting to the database: " . mysql_error());
mysql_select_db(DB_NAME, $mysqlc) or die("could not detect the database: " . mysql_error());

function mysql_shutdown()
{
	global $mysqlc;
	global $mysqli;
	mysql_close($mysqlc) or die("error closing the connection: " . mysql_error());
	$mysqli->close();
}

register_shutdown_function('mysql_shutdown');

?>
