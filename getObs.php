<?php
  ini_set('memory_limit', '512M');
  $providers = explode(',',getenv('providers'));
  array_push($providers,'glosTDS');

  $data = array();
  $dbconn = new PDO('sqlite:db/json.sqlite3');
  foreach ($providers as $p) {
    $result = $dbconn->query("select f from json where providers = '$p' and ready = 1 order by seq desc limit 1");
    foreach ($result as $line) {
      $data = array_merge($data,json_decode(file_get_contents($line[0]),true));
    }
  }

  if (isset($_REQUEST['csv'])) {
    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename="stations.csv"');
    $stdout = fopen('php://output','w');
    fputcsv($stdout,array(
       'provider'
      ,'description'
      ,'variables'
    ));
    foreach ($data as $d) {
      fputcsv($stdout,array(
         $d['properties']['provider']
        ,$d['properties']['descr']
        ,implode(',',array_keys($d['properties']['topObs']))
      ));
    }
    fclose($stdout);
  }
  else {
    header('Content-type: application/json');
    echo json_encode($data);
  }
?>
