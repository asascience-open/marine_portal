<?php
  ini_set('memory_limit','256M');
  $dbUser = getenv('dbUser');
  $dbPass = getenv('dbPass');
  $dbName = getenv('dbName');
  $dbPort = getenv('dbPort');

  header('Content-type: application/json');
  $dbconn = pg_connect("host=localhost dbname=$dbName user=$dbUser password=$dbPass port=$dbPort");
  $result = pg_query("select f from json order by seq desc limit 1");
  while ($line = pg_fetch_array($result)) {
    $json = json_decode(file_get_contents($line[0]),true);
  }
  pg_close($dbconn);

  for ($i = 0; $i < count($json); $i++) {
    if (!is_null($json[$i]['properties']['timeSeries'])) {
      foreach ($json[$i]['properties']['timeSeries'] as $k => $v) {
        unset($json[$i]['properties']['timeSeries'][$k]);
      }
    } 
  }

  echo json_encode($json);
?>
