<?php

echo "disabled\n"; exit;

require("include/mysql_connect.php");
mysql_query("DROP TABLE IF EXISTS ".DB_TABLE.";") or die(mysql_error());
mysql_query("CREATE TABLE ".DB_TABLE." (
  id INT NOT NULL AUTO_INCREMENT,
  sig_type VARCHAR(3) NOT NULL,
  cod_count INT NOT NULL,
  result_success INT NOT NULL,
  result_failure INT NOT NULL,
  response_time INT NOT NULL,
  cod_size BIGINT UNSIGNED NOT NULL,
  retry INT NOT NULL,
  date TIMESTAMP default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
  PRIMARY KEY(id)
  ) engine=InnoDB AUTO_INCREMENT=1;") or die(mysql_error());

?>
