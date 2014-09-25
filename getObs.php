<?php
  ini_set('memory_limit', '512M');
  $dbUser = getenv('dbUser');
  $dbPass = getenv('dbPass');
  $dbName = getenv('dbName');
  $dbPort = getenv('dbPort');
  $providers = explode(',',getenv('providers'));
  array_push($providers,'glosTDS');

  $data = array();
  $dbconn = pg_connect("host=localhost dbname=$dbName user=$dbUser password=$dbPass port=$dbPort");
  foreach ($providers as $p) {
    $result = pg_query("select f from json where providers = '$p' order by seq desc limit 1");
    while ($line = pg_fetch_array($result)) {
      $data = array_merge($data,json_decode(file_get_contents($line[0]),true));
    }
  }
  pg_close($dbconn);

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
