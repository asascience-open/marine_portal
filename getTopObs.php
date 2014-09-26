<?php
  ini_set('memory_limit','256M');
  $providers = explode(',',getenv('providers'));

  $data = array();

  header('Content-type: application/json');
  $dbconn = new PDO('sqlite:db/json.sqlite3');
  foreach ($providers as $p) {
    $result = $dbconn->query("select f from json where providers = '$p' and ready = 1 order by seq desc limit 1");
    foreach ($result as $line) {
      $json = json_decode(file_get_contents($line[0]),true);
    }
    for ($i = 0; $i < count($json); $i++) {
      if (!is_null($json[$i]['properties']['timeSeries'])) {
        foreach ($json[$i]['properties']['timeSeries'] as $k => $v) {
          unset($json[$i]['properties']['timeSeries'][$k]);
        }
      } 
      array_push($data,$json[$i]);
    }
  }

  echo json_encode($data);
?>
