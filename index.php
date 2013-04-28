<?php

require("include/mysql_connect.php");
require("include/common_sql.php");

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
    <hr>
    <?php show_log('RBB', 'RIM Blackberry Apps') ?><br />
    <hr>
    <?php show_log('RCR', 'RIM Crypto [RIM]') ?><br />
    <hr>
    <?php show_log('PBK', 'RDK/PBDT - PlayBook / BB10') ?><br />
    <hr>
    Having trouble with code signing? See <a href="https://developer.blackberry.com/CodeSigningHelp">here</a> for common issues and <a href="https://developer.blackberry.com/CodeSigningHelp">Code Signing Support</a>.<br />
    Developer support( <b>devsupport@rim.com</b> / <a href="http://twitter.com/BlackBerryDev"><b>@BlackBerryDev</b></a> / <b>1-877-255-2377</b> )<br />
    See <a href="https://www.blackberry.com/SignedKeys">here</a> for more information about BlackBerry <a href="https://www.blackberry.com/SignedKeys">Code Signing Keys</a>.<!--http://us.blackberry.com/developers/javaappdev/codekeys.jsp-->
    <hr>
    Follow the results on Twitter <a href="http://twitter.com/SigningServer">@SigningServer</a> | <a href="http://martinmreed.net">martinmreed.net</a><br />
  </body>
</html>
<?php

function show_log($key, $name) {

  $pbk = strcasecmp($key, 'pbk') == 0;
  $rcc = strcasecmp($key, 'rcc') == 0;

  $green = '#03C03C';
  $red = '#C23B22';
  $blue = '#009ACD';
  
  if ($pbk) {
    echo "<font size=\"5\"><b>$name</b></font> <font size=\"3\">(<font size=\"3\" color=\"$red\"><b>BETA</b></font>)</font>";
  }
  else {
    echo "<font size=\"5\"><b>$key - $name</b></font>";
  }

  $last_checkin = last_checkin($key);

  if ($rcc) {
    echo "<br /><font size=\"2\">* Note: RCC has limited check-ins available</font>";
  }

  if (!$last_checkin['valid']) {
    echo "<br /><font size=\"6\" color=\"$red\"><b>0</b></font><font size=\"6\"> check-ins</font>";
    return;
  }

  echo "<font size=\"4\">";

  if (!$rcc) {
    $results = results($key);
  }

  if (!$rcc) {
    if ($last_checkin['failures'] == 0) {
      $uptime = last_failure($key);
      if (!$uptime) $uptime = first_success($key);
      if ($uptime) {
        echo "<br />uptime( ";
        echo get_timestamp($uptime);
        echo " )";
      }
    }
    else {
      $downtime = last_success($key);
      if (!$downtime) $downtime = first_failure($key);
      if ($downtime) {
        echo "<br />downtime( ";
        echo "<font color=\"$red\">";
        echo get_timestamp($downtime);
        echo "</font> )";
      }
    }

    $success_day = $results['success_day'];
    $success_month = $results['success_month'];

    echo "<br />health( ";
    $health_color = $success_day > 95 ? $green : $red;
    echo "<font color=\"$health_color\"><b>$success_day%</b></font> day / ";
    $health_color = $success_month > 95 ? $green : $red;
    echo "<font color=\"$health_color\"><b>$success_month%</b></font> month )";
  }

  $overdue = $last_checkin['time_since'] >= 60 * 10;  

  if (!$overdue) {
    echo "<br />latest( ";
    if ($last_checkin['failures'] == 0) {
      echo "<font color=\"$green\"><b>SUCCESS</b></font>";
    }
    else {
      echo "<font color=\"$red\"><b>FAILURE</b></font>";
    }
    echo " / ";
    echo get_timestamp($last_checkin['time_since']);
    echo " ago )";
    
    if (!$rcc) {
      $avg_speed = $results['avg_speed'];
      $speed = $last_checkin['speed'];
      echo "<br />speed( $speed / $avg_speed avg )";
    }
  }

  if (!$rcc) {
    echo "<br />chart( <a style=\"color:$blue\" href=\"chart/index.php?sigType=$key\">24h</a>";
    echo " / <a style=\"color:$blue\" href=\"chart/index.php?sigType=$key&days=7\">7d</a>";
    if (!$pbk) {
      echo " / <a style=\"color:$blue\" href=\"chart/index.php?sigType=all\">composite</a>";
    }
    echo " )";
  }

  if ($overdue) {
    if (!$rcc) {
      echo "<br /><font size=\"6\" color=\"$red\"><b>";
      echo get_timestamp($last_checkin['time_since']);
      echo "</b></font> since the last check-in <font size=\"2\">...meh</font>";
      echo "<br /><font size=\"3\" color=\"$red\"><b>You may be seeing this due to very slow response times</b></font>";
    }
    echo "</font>";
    return;
  }
  
  echo "</font>";
}

?>
