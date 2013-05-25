<?php

echo "disabled\n"; exit;

require("include/mysql_connect.php");
mysql_query("DROP TABLE IF EXISTS ".DB_TABLE.";") or die(mysql_error());
mysql_query("CREATE TABLE ".DB_TABLE." (
  id INT NOT NULL AUTO_INCREMENT,
  signature VARCHAR(3) NOT NULL,
  count INT NOT NULL,
  successes INT NOT NULL,
  failures INT NOT NULL,
  duration INT NOT NULL,
  size BIGINT UNSIGNED NOT NULL,
  retries INT NOT NULL,
  date TIMESTAMP default CURRENT_TIMESTAMP NOT NULL,
  PRIMARY KEY(id)
  ) engine=InnoDB AUTO_INCREMENT=1;") or die(mysql_error());

?>
