<?php

require("include/mysql_connect.php");
require("include/common_sql.php");

$green = '#03C03C';
$red = '#C23B22';
$blue = '#009ACD';
$gray = '868686';

?>
<html>
  <head>
    <title>Is the signing server down?</title>
    <meta http-equiv="Refresh" content="600" />
    <style>
      a {text-decoration:none}
    </style>
  </head>
  <body>
    <?php show_log('RRT', 'RIM Runtime') ?><br />
    <hr color="#EEEEEE">
    <?php show_log('RBB', 'RIM Blackberry Apps') ?><br />
    <hr color="#EEEEEE">
    <?php show_log('RCR', 'RIM Crypto') ?><br />
    <hr color="#EEEEEE">
    <?php show_log('RCC', 'Crypto Certicom') ?><br />
    <hr color="#EEEEEE">
    <?php show_log('PBK', 'RDK/PBDT - BlackBerry 10') ?><br />
    <hr color="#EEEEEE">
    Having trouble with code signing? See <a href="https://developer.blackberry.com/CodeSigningHelp">here</a> for common issues and <a href="https://developer.blackberry.com/CodeSigningHelp">Code Signing Support</a>.<br />
    Developer support( <a href="http://supportforums.blackberry.com/t5/Application-Platforms/ct-p/app_plat"><b>Forums</b></a> / <a href="http://twitter.com/BlackBerryDev"><b>@BlackBerryDev</b></a> / <b>1-877-255-2377</b> )<br />
    See <a href="https://www.blackberry.com/SignedKeys">here</a> for more information about BlackBerry <a href="https://www.blackberry.com/SignedKeys">Code Signing Keys</a>.<br />
    <font color="<?php echo $gray ?>"><b>legacy</b></font>: Not using <a href="http://devblog.blackberry.com/2013/08/code-signing-keys-be-gone-welcome-blackberry-id">BlackBerry ID</a><br />
    <hr color="#EEEEEE">
    <a href="https://github.com/martinmreed/signingserver-com" title="Source on GitHub"><img src="github_30.png"></a> <a href="stats.sql" title="Download Database"><img src="sql_30.png"></a>
  </body>
</html>
<?php

function show_log($key, $name)
{
	global $green;
	global $red;
	global $blue;
	global $gray;
	
	$pbk = strcasecmp($key, 'pbk') == 0;
	
        if ($pbk) {
                echo "<font size=\"5\"><b>$name</b></font> <font size=\"3\">(<font color=\"$red\"><b>beta</b></font> / <font color=\"$gray\"><b>legacy</b></font>)</font>";
        } else {
                echo "<font size=\"5\"><b>$key - $name</b></font> <font size=\"3\">(<font color=\"$gray\"><b>legacy</b></font>)</font>";
        }

        $last_checkin = last_checkin($key);
	
	if (!$last_checkin['valid']) {
		echo "<br /><font size=\"6\" color=\"$red\"><b>0</b></font><font size=\"6\"> check-ins</font>";
		return;
	}
	
	echo "<font size=\"4\">";
	
	$results = results($key);
        $success_year = $results['success_year'];
        $success_year_start = $results['success_year_start'];
	
	if ($last_checkin['failures'] == 0)
	{
		$uptime = last_failure($key);
		if (!$uptime) $uptime = first_success($key);
		if ($uptime) {
			echo "<br />uptime( ";
			echo get_timestamp($uptime);
			echo " / ";
		}
	}
	else
	{
		$downtime = last_success($key);
		if (!$downtime) $downtime = first_failure($key);
		if ($downtime) {
			echo "<br />downtime( ";
			echo "<font color=\"$red\">";
			echo get_timestamp($downtime);
			echo "</font> / ";
		}
	}
	
	if (!$uptime && !$downtime)
	{
		echo "<br />uptime( ";
	}
	
	$health_color = $success_year > 95 ? $green : $red;
	echo "<font color=\"$health_color\"><b>$success_year%</b></font> ".$success_year_start." )";
	
	$overdue = $last_checkin['time_since'] >= 60 * 10;	
	
	if (!$overdue)
	{
		echo "<br />latest( ";
		if ($last_checkin['failures'] == 0) {
			echo "<font color=\"$green\"><b>SUCCESS</b></font>";
		} else {
			echo "<font color=\"$red\"><b>FAILURE</b></font>";
		}
		echo " / ";
		echo get_timestamp($last_checkin['time_since']);
		echo " ago )";
		
		$avg_speed = $results['avg_speed'];
		$speed = $last_checkin['speed'];
		echo "<br />speed( $speed / $avg_speed avg )";
	}
	
	echo "<br />chart( <a style=\"color:$blue\" href=\"chart/index.php?sigType=$key\">24h</a>";
	echo " / <a style=\"color:$blue\" href=\"chart/index.php?sigType=$key&days=7\">7d</a>";
	if (!$pbk) {
		echo " / <a style=\"color:$blue\" href=\"chart/index.php?sigType=all\">composite</a>";
	}
	echo " )";

	if ($overdue)
	{
		echo "<br /><font size=\"6\" color=\"$red\"><b>";
		echo get_timestamp($last_checkin['time_since']);
		echo "</b></font> since the last check-in <font size=\"2\">...meh</font>";
		echo "<br /><font size=\"3\" color=\"$red\"><b>You may be seeing this due to very slow response times</b></font>";
		echo "</font>";
		return;
	}
	
	echo "</font>";
}

?>
