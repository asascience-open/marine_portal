<?php
  header('Content-type:text/javascript');

  $data = array();
  $dbconn = new PDO('sqlite:db/json.sqlite3');
  foreach (explode(',',$_REQUEST['providers']) as $p) {
    $pro = $p;
    if ($p == 'ndbc' || $p == 'coops') {
      $pro = 'sos';
    }
    $result = $dbconn->query("select f from json where providers = '$pro' and ready = 1 order by seq desc limit 1");
    foreach ($result as $line) {
      $json = json_decode(file_get_contents($line[0]),true);
      foreach ($json as $j) {
        $j['properties']['dataurl'] = sprintf(
           "http://data.glos.us/portal/getObsByPlatform.php?provider=%s&platform=%s"
          ,$p
          ,urlencode($j['properties']['descr'])
        );
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

  array_multisort(
    array_map(
      function($d) {
        return strtoupper($d['properties']['descr']);
      }
      ,$data
    )
    ,SORT_ASC
    ,$data
  );

  date_default_timezone_set('UTC');
  $d = array(array(
     'Name'
    ,'Lon'
    ,'Lat'
    ,'Tmin'
    ,'Tmax'
    ,'Abstract'
    ,'Keywords'
    ,'Categories'
    ,'ProviderURL'
    ,'DataURL'
  ));
  foreach ($data as $k => $v) {
    $p = $v['properties'];
    $tmax = null;
    if (array_key_exists('topObs',$p)) {
      $t = $p['topObs'][array_pop(array_keys($p['topObs']))]['t'];
      if ($t != '') {
       $tmax = date('c',$t);
      }
    } 
    array_push($d,array(
       'name'         => $p['descr']
      ,'lon'          => $p['lon']
      ,'lat'          => $p['lat']
      ,'tmin'         => null
      ,'tmax'         => $tmax
      ,'abstract'     => $p['abstract']
      ,'keywords'     => implode(', ',array_keys($p['topObs']))
      ,'categories'   => ''
      ,'provider_url' => $p['url']
      ,'data_url'     => $p['dataurl']
    ));
  }

  echo json_encode(array_slice($d,1));

  $fp = fopen('/tmp/json.csv','w');
  foreach ($d as $fields) {
    fputcsv($fp,$fields);
  }
  fclose($fp);

?>
