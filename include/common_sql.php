<?php

function status_all($sigs)
{
  $result = sprintf("{");
  $result .= sprintf("\"version\":%d", 1);
  $result .= sprintf(",\"sigs\":[");
  for ($i = 0; $i < count($sigs); $i++)
  {
    $key = strtoupper($sigs[$i]);
    $status = status($key);
    if (!isset($status)) continue;
    if ($i > 0) $result .= sprintf(",");
    $result .= $status;
  }
  $result .= sprintf("]}");
  return $result;
}

function status($key)
{
  $key = strtoupper($key);
  $val = last_checkin($key);
  if (!$val['valid']) return;
  $result = sprintf("{\"sig\":\"%s\"", $key);
  $result .= sprintf(",\"success\":%s", $val['failures'] ? "false" : "true");
  $result .= sprintf(",\"repeat\":%d", failures($key, $val['failures']));
  $result .= sprintf(",\"speed\":%s", $val['speed']);
  $result .= sprintf(",\"aspeed\":%s", avg_speed($key));
  $result .= sprintf(",\"date\":%ld", strtotime($val['date']));
  $result .= sprintf("}");
  return $result;
}

function failures($key, $failures)
{
  global $mysqli;

  $statement = $mysqli->prepare("SELECT COUNT(id)
    FROM ".DB_TABLE."
    WHERE id > (SELECT MAX(id)
      FROM ".DB_TABLE."
      WHERE result_failure ".($failures?"=":"!=")." 0
      AND sig_type = ?)
    AND sig_type = ?;");
  $statement->bind_param("ss", $key, $key);
  $statement->execute();
  $statement->bind_result($count);
  $fetched = $statement->fetch();
  $statement->close();
  return $count;
}

function avg_speed($key)
{
  global $mysqli;
  
  $statement = $mysqli->prepare("SELECT cod_count,
      response_time
      FROM ".DB_TABLE."
      WHERE sig_type = ?
      AND date >= DATE_SUB(NOW(), INTERVAL 24 HOUR);");
  $statement->bind_param("s", $key);
  $statement->execute();
  $statement->bind_result($cod_count, $response_time);
  $fetched = $statement->fetch();
  
  $samples = array();
  
  for ($i = 0; $row = $statement->fetch(); $i++)
  {
    $samples[$i] = ($response_time / 1000) / $cod_count;
  }

  $statement->close();
  
  sort($samples);
  $sample_count = count($samples);

  $uqi = ($sample_count-1) * 0.75;
  if (floor($uqi) != $uqi)
  {
    $uq = ($samples[floor($uqi)] + $samples[ceil($uqi)]) / 2;
  }
  else
  {
    $uq = $samples[$uqi];
  }

  $lqi = ($sample_count-1) * 0.25;
  if (floor($lqi) != $lqi)
  {
    $lq = ($samples[floor($lqi)] + $samples[ceil($lqi)]) / 2;
  }
  else
  {
    $lq = $samples[$lqi];
  }
  
  $iqr = $uq - $lq;
  
  $results = array();
  
  $lr = $lq-(1.5*$iqr);
  $ur = $uq+(1.5*$iqr);
  for ($i = 0; $i < $sample_count; $i++)
  {
    if ($samples[$i] >= $lr && $samples[$i] <= $ur)
    {
      array_push($results, $samples[$i]);
    }
  }

  $result_count = count($results);
  return sprintf("%01.2f", array_sum($results) / $result_count, 2);
}

function last_checkin($key)
{
  global $mysqli;

  $statement = $mysqli->prepare("SELECT date,
    result_failure,
    response_time,
    cod_count
    FROM ".DB_TABLE."
    WHERE id = (SELECT MAX(id)
      FROM ".DB_TABLE."
      WHERE sig_type = ?);");
  $statement->bind_param("s", $key);
  $statement->execute();
  $statement->bind_result($row['date'], $row['failures'], $response_time, $cod_count);
  $fetched = $statement->fetch();
  $statement->close();

  if (!$fetched)
  {
    $row['valid'] = false;
    return $row;
  }

  $row['valid'] = !empty($row['date']);
  $row['time_since'] = time() - strtotime($row['date']);
  $row['speed'] = sprintf("%01.2f", ($response_time / 1000) / $cod_count, 2);
  return $row;
}

function first_failure($key)
{
  global $mysqli;

  $statement = $mysqli->prepare("SELECT MIN(date)
    FROM ".DB_TABLE."
    WHERE sig_type = ?
    AND result_failure != 0;");
  $statement->bind_param("s", $key);
  $statement->execute();
  $statement->bind_result($date);
  $fetched = $statement->fetch();
  $statement->close();
  return !$fetched || !isset($date) ? 0 : time() - strtotime($date);
}

function last_failure($key)
{
  global $mysqli;

  $statement = $mysqli->prepare("SELECT MAX(date)
    FROM ".DB_TABLE."
    WHERE sig_type = ?
    AND result_failure != 0;");
  $statement->bind_param("s", $key);
  $statement->execute();
  $statement->bind_result($date);
  $fetched = $statement->fetch();
  $statement->close();
  return !$fetched || !isset($date) ? 0 : time() - strtotime($date);
}

function first_success($key)
{
  global $mysqli;

  $statement = $mysqli->prepare("SELECT MIN(date)
    FROM ".DB_TABLE."
    WHERE sig_type = ?
    AND result_failure = 0;");
  $statement->bind_param("s", $key);
  $statement->execute();
  $statement->bind_result($date);
  $fetched = $statement->fetch();
  $statement->close();
  return !$fetched || !isset($date) ? 0 : time() - strtotime($date);
}

function last_success($key)
{
  global $mysqli;

  $statement = $mysqli->prepare("SELECT MAX(date)
    FROM ".DB_TABLE."
    WHERE sig_type = ?
    AND result_failure = 0;");
  $statement->bind_param("s", $key);
  $statement->execute();
  $statement->bind_result($date);
  $fetched = $statement->fetch();
  $statement->close();
  return !$fetched || !isset($date) ? 0 : time() - strtotime($date);
}

function results($key)
{
  global $mysqli;

  $statement = $mysqli->prepare("SELECT SUM(result_success),
    SUM(result_failure),
    SUM(response_time),
    SUM(cod_count)
    FROM ".DB_TABLE."
    WHERE sig_type = ?
    AND date >= (SELECT MIN(date)
      FROM ".DB_TABLE."
      WHERE sig_type = ?
      AND date >= DATE_SUB(NOW(), INTERVAL 1 DAY));");
  $statement->bind_param("ss", $key, $key);
  $statement->execute();
  $statement->bind_result($success, $failure, $response_time, $cod_count);
  $fetched = $statement->fetch();
  $statement->close();

  $success_rate = ($success / ($success + $failure)) * 100;
  $row['success_rate'] =  round($success_rate, ($success_rate >= 99 || $success_rate <= 1) ? 2 : 0);

  $row['avg_speed'] = avg_speed($key);

  return $row;
}

function get_timestamp($date_diff)
{
  $MINUTE = 60;
  $HOUR = $MINUTE * 60;
  $DAY = $HOUR * 24;
  $YEAR = $DAY * 365;
  
  $years = floor($date_diff / $YEAR);
  $date_diff -= $years * $YEAR;
  
  $days = floor($date_diff / $DAY);
  $date_diff -= $days * $DAY;
  
  $hours = floor($date_diff / $HOUR);
  $date_diff -= $hours * $HOUR;
  
  $minutes = floor($date_diff / $MINUTE);
  $date_diff -= $minutes * $MINUTE;
  
  $timestamp = "";

  if ($years > 0) $timestamp .= $years."y ";
  if ($days > 0) $timestamp .= $days."d ";
  if ($hours > 0) $timestamp .= $hours."h ";
  if ($minutes > 0) $timestamp .= $minutes."m ";
  
  $timestamp = trim($timestamp);
  
  if (empty($timestamp)) $timestamp = $date_diff."s";
  
  return $timestamp;
}

?>
