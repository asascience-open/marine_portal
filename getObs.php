<?php
  $dbUser = getenv('dbUser');
  $dbPass = getenv('dbPass');
  $dbName = getenv('dbName');
  $dbPort = getenv('dbPort');

  header('Content-type: application/json');
  $dbconn = pg_connect("host=localhost dbname=$dbName user=$dbUser password=$dbPass port=$dbPort");
  $result = pg_query("select f from json order by seq desc limit 1");
  while ($line = pg_fetch_array($result)) {
    echo file_get_contents($line[0]);
  }
  pg_close($dbconn);
?>
