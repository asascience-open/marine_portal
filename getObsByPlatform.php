<?php
  header('Content-type:text/javascript');

  $data = array();
  $dbconn = new PDO('sqlite:db/json.sqlite3');
  foreach (explode(',',$_REQUEST['provider']) as $p) {
    $pro = $p;
    if ($p == 'ndbc' || $p == 'coops') {
      $pro = 'sos';
    }
    $result = $dbconn->query("select f from json where providers = '$pro' and ready = 1 order by seq desc limit 1");
    foreach ($result as $line) {
      $json = json_decode(file_get_contents($line[0]),true);
      foreach ($json as $j) {
        if ($j['properties']['descr'] == $_REQUEST['platform']) {
          if ($pro == 'sos') {
            if ($p == 'ndbc' && strpos($j['properties']['url'],'ndbc')) {
              array_push($data,$j);
            }
            else if ($p == 'coops' && strpos($j['properties']['url'],'tidesandcurrents')) {
              array_push($data,$j);
            }
          }
          else {
            array_push($data,$j);
          }
        }
      }
    }
  }

  echo json_encode($data);
?>
