<?php

// accepted signatures
define("VALID_SIG", "rrt,rbb,rcr,pbk");
define("VALID_CHART_SIG", VALID_SIG.",all");
define("VALID_CHART_SIG_COMP", "rrt,rbb,rcr,all");

// apikey for submit.php
define("SUBMISSION_KEY", "");

// database connection
define("DB_HOST", "");
define("DB_USER", "");
define("DB_PASSWD", "");
define("DB_NAME", "");
define("DB_TABLE", "");

// twitter connection
define("CONSUMER_KEY", "");
define("CONSUMER_SECRET", "");
define("OAUTH_TOKEN", "");
define("OAUTH_SECRET", "");

// number of failures before it goes to twitter
define("TWEETER_THRESHOLD", "3");

?>
