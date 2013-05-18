<?php

if(!fsockopen("www.google.com", 80)){
  exit_with_error_code(10);
}

define("ADDRESS_WHITE_LIST", "127.0.0.1,192.168.1.10");
if (!strstr(ADDRESS_WHITE_LIST, $_SERVER['REMOTE_ADDR'])) {
  exit_with_error_code(9);
}

define("TWEETER_THRESHOLD", "3");

require("include/mysql_connect.php");
require("include/common_sql.php");
require("tweet/twitteroauth.php");

$json = file_get_contents('php://input');
$json = json_decode($json);
$results = $json->{'results'};

tweet_results($results);

$signerIds = array();

foreach ($results as $result) {
  array_push($signerIds, $result->{'signerId'});
  store_result($result);
}

echo "Successfully saved results to isthesigningserverdown.com for ".implode($signerIds, ", ");

exit;

function exit_with_error_code($exitCode) {
  header("HTTP/1.0 400 Bad Request (".$exitCode.")");
  echo $exitCode;
  exit($exitCode);
}

function tweet_results($results) {

  $now_succeeding = array();
  $now_failing = array();

  foreach ($results as $result) {
    sort_result($result, $now_succeeding, $now_failing);
  }
  
  if (count($now_succeeding) == 0 && count($now_failing) == 0) {
    return;
  }
  
  $tweet = "At ".date('H:i (T)').":";
  
  if (count($now_succeeding) > 0) {
    $tweet .= " ".glue_result_text($now_succeeding)." succeeding.";
  }
  
  if (count($now_failing) > 0) {
    $tweet .= " ".glue_result_text($now_failing)." failing.";
  }

  $connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, OAUTH_TOKEN, OAUTH_SECRET);
  $content = $connection->get('account/verify_credentials');
  $connection->post('statuses/update', array('status' => $tweet));
}

function glue_result_text($results) {
  $results_text = "";
  for ($i = 0; $i < count($results) - 1; $i++) {
    if ($i > 0) $results_text .= ", ";
    $results_text .= strtoupper($results[$i]);
  }
  if (!empty($results_text)) $results_text .= " and ";
  $results_text .= strtoupper($results[count($results) - 1]);
  $results_text .= count($results) > 1 ? " are" : " is";
  return $results_text;
}
  
function sort_result($result, &$now_succeeding, &$now_failing) {
  
  $key = $result->{'signerId'};

  $failures = failures($key, true);
  if ($failures == 0) {
    return;
  }  

  $failure = $result->{'failure'} > 0;

  if ($failure && $failures == TWEETER_THRESHOLD - 1) {
    array_push($now_failing, $key);
  }
  else if (!$failure && $failures >= TWEETER_THRESHOLD) {
    array_push($now_succeeding, $key);
  }
}

function store_result($result) {

  global $mysqli;
  
  $sigType = strtolower($result->{'signerId'});

  $statement = $mysqli->prepare("INSERT INTO ".DB_TABLE."
    SET sig_type=?,
    cod_count=?,
    result_success=?,
    result_failure=?,
    response_time=?,
    cod_size=?,
    retry=?;");
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
