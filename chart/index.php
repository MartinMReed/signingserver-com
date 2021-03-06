<?php

  $sigType = strtolower($_GET['sigType']);

  require("../include/constants.php");

  if (!strstr(VALID_CHART_SIG, $sigType))
  {
    echo "Invalid signature";
    exit;
  }

  $days = $_GET['days'];

  if (!isset($days)) $days = 1;

  if (!is_numeric($days) || $days < 1 || (strcmp("all", $sigType) == 0 && $days > 1))
  {
    header('Location: index.php?sigType='.$sigType.'&days=1');
    exit;
  }

  if ($days > 7)
  {
    header('Location: index.php?sigType='.$sigType.'&days=7');
    exit;
  }

  require("../include/mysql_connect.php");
  require("../include/common_sql.php");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Refresh" content="600">
    <title><?php echo strtoupper($sigType); ?></title>
    <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
    <script type="text/javascript" src="js/highcharts.js"></script>
    <script type="text/javascript">
var ddate = new Date();

function _t(e) { return (e * 1000) - (ddate.getTimezoneOffset() * 60 * 1000); }

var ddate_str = ddate.toTimeString();
var timezone = ddate_str.substring(ddate_str.length - 4, ddate_str.length - 1);

$(document).ready(chartylolol);

function chartylolol()
{
    var chart = new Highcharts.Chart({
        chart: {
            renderTo: 'container',
            zoomType: 'xy',
            spacingTop: 0,
            spacingRight: 0,
            spacingBottom: 0,
            spacingLeft: 0
        },
        title: {
            text: 'Average response time per COD'
        },
        xAxis: {
            type: 'datetime',
            min: _t(<?php start_date(); ?>),
            max: _t(<?php end_date(); ?>),
            title: {
                text: 'Hour (GMT' + (-ddate.getTimezoneOffset() / 60) + ')'
            },
            gridLineWidth: 1
        },
        yAxis: {
            <?php $yval = yval($sigType);
                  echo "min: $yval[0],";
                  echo "max: $yval[1],";?>
            title: {
                text: 'Response Time (seconds)'
            }
        },
        tooltip: {
            formatter: function () {
                return Highcharts.dateFormat('%b %e %Y, %H:%M', this.x) + ': ' + this.y + ' s';
            }
        },
        legend: {
            layout: 'vertical',
            align: 'left',
            verticalAlign: 'top',
            x: 100,
            y: 70,
            backgroundColor: '#FFFFFF',
            borderWidth: 1
        },
        plotOptions: {
            scatter: {
                marker: {
                    radius: 5,
                    states: {
                        hover: {
                            enabled: true,
                            lineColor: 'rgb(100,100,100)'
                        }
                    }
                },
                states: {
                    hover: {
                        marker: {
                            enabled: false
                        }
                    }
                }
            }
        },
        series: [<?php

  if (strcmp("all", $sigType) != 0)
  {
    echo "{
            name: 'Success',
            color: 'rgba(119, 152, 191, 0.50)',
            data: ";
    populate_data($sigType, "failures = 0");
    echo ",
            type: 'scatter'
        }, {
            name: 'Failure',
            color: 'rgba(223, 83, 83, 0.90)',
            data: ";
    populate_data($sigType, "failures != 0");
    echo ",
            type: 'scatter'
        }, {
            name: 'Over Capacity',
            color: 'rgba(223, 83, 3, 0.90)',
            data: ";
    populate_data($sigType, "failures = 0 AND retries != 0");
    echo ",
            type: 'scatter'
        }";
  }
  else
  {
    $sigTypes = get_signatures();
    $colors = array("0,173,239", "140,198,62", "254,164,15", "255,16,16");
    for ($i = 0; $i < count($sigTypes); $i++)
    {
      if ($i > 0) echo ",";
      echo "{
            name: '".strtoupper($sigTypes[$i])."',
            color: 'rgba(".$colors[$i].", 0.50)',
            data: ";
      populate_data($sigTypes[$i], "count != 0");
      echo ",
            type: 'scatter'
        }";
    }
  }
       ?>]
    });
}

function f_clientWidth()
{
    return f_filterResults(
    window.innerWidth ? window.innerWidth : 0,
    document.documentElement ? document.documentElement.clientWidth : 0,
    document.body ? document.body.clientWidth : 0);
}

function f_clientHeight()
{
    return f_filterResults(
    window.innerHeight ? window.innerHeight : 0,
    document.documentElement ? document.documentElement.clientHeight : 0,
    document.body ? document.body.clientHeight : 0);
}

function f_filterResults(n_win, n_docel, n_body)
{
    var n_result = n_win ? n_win : 0;
    if (n_docel && (!n_result || (n_result > n_docel))) n_result = n_docel;
    return n_body && (!n_result || (n_result > n_body)) ? n_body : n_result;
}
    </script>
  </head>
  <body onresize="window.location.href = window.location.href;">
    <div id="container" style="margin: 0 auto"></div>
    <script language="JavaScript">
      document.getElementById("container").style.height = (f_clientHeight()-16)+"px";
      document.getElementById("container").style.width = f_clientWidth()+"px";
    </script>
  </body>
</html>
<?php

  function get_signatures()
  {
    $sigTypes = explode(",", VALID_CHART_SIG_COMP);
    unset($sigTypes[array_search("all", $sigTypes)]);
    return $sigTypes;
  }

  function populate_data($sigType, $where)
  {
    global $days;
    global $allTypes;
    
    $result = mysql_query("SELECT date,
      count,
      duration
      FROM ".DB_TABLE."
      WHERE signature = '$sigType'
      AND $where
      AND date >= DATE_SUB(NOW(), INTERVAL $days DAY);") or die(mysql_error());
      
    echo "[";

    $samples = array();
    $rows = array();
    
    for ($i = 0; $row = mysql_fetch_assoc($result); $i++)
    {
      $response = ($row["duration"] / 1000 ) / $row["count"];
      array_push($rows, $row);
      array_push($samples, $response);
    }

    mysql_free_result($result);

    $outliers = outliers($samples);
    $lr = $outliers[0];
    $ur = $outliers[1];

    $added_count = 0;

    foreach ($rows as $row)
    {
      $response = ($row["duration"] / 1000 ) / $row["count"];
      if ($response < $lr || $response > $ur) continue;

      if ($added_count > 0) echo ",";
      $added_count++;

      $time = strtotime($row["date"]);
      $daysago = floor((time()-$time) / (24*60*60));
      $time += (24*60*60*$daysago);

      echo "[_t($time),$response]";
    }

    echo "]";
  }

  function yval($sigType)
  {
    global $days;
    global $allTypes;

    if (strcmp("all", $sigType) != 0)
    {
      $where = "signature = '$sigType'";
    }
    else
    {
      $sigTypes = get_signatures();
      $where = "signature in ('".implode("','", $sigTypes)."')";
    }

    $result = mysql_query("SELECT count,
      duration
      FROM ".DB_TABLE."
      WHERE $where
      AND date >= DATE_SUB(NOW(), INTERVAL $days DAY);") or die(mysql_error());

    $samples = array();

    for ($i = 0; $row = mysql_fetch_assoc($result); $i++)
    {
      $response = ($row["duration"] / 1000 ) / $row["count"];
      array_push($samples, $response);
    }

    mysql_free_result($result);

    return outliers($samples);
  }

  function start_date()
  {
    $result = mysql_query("SELECT DATE_SUB(NOW(), INTERVAL 1 DAY) as date;") or die(mysql_error());
    $row = mysql_fetch_assoc($result);
    echo strtotime($row["date"]);
    mysql_free_result($result);
  }

  function end_date()
  {
    $result = mysql_query("SELECT NOW() as date;") or die(mysql_error());
    $row = mysql_fetch_assoc($result);
    echo strtotime($row["date"]);
    mysql_free_result($result);
  }

?>
