<?php
  $dbUser = getenv('dbUser');
  $dbPass = getenv('dbPass');
  $dbName = getenv('dbName');
  $dbPort = getenv('dbPort');
  $providers = explode(',',getenv('providers'));

  $data = array();

  header('Content-type: application/json');
  $dbconn = pg_connect("host=localhost dbname=$dbName user=$dbUser password=$dbPass port=$dbPort");
  foreach ($providers as $p) {
    $result = pg_query("select f from json where providers = '$p' order by seq desc limit 1");
    while ($line = pg_fetch_array($result)) {
      $data = array_merge($data,json_decode(file_get_contents($line[0]),true));
    }
  }
  pg_close($dbconn);

  echo json_encode($data);
?>
