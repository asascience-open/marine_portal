<?php
  header('Content-type:text/javascript');

  $cat_csv = array();
  $data    = array();
  $dbconn = new PDO('sqlite:db/json2.sqlite3');
  foreach (explode(',',$_REQUEST['providers']) as $p) {
    $pro = $p;
    if ($p == 'ndbc' || $p == 'coops') {
      $pro = 'sos';
    }
    if ($p == 'glos') {
      // $cat_csv['glos'] = csv_to_array(explode("\n",file_get_contents('https://docs.google.com/feeds/download/spreadsheets/Export?key=1bXkHu98EX6Tqkhz_9CSaopST27jZd9YvcotFTmJYl5o&exportFormat=csv&gid=0')));
      $cat_csv['glos'] = csv_to_array(explode("\n",file_get_contents('https://docs.google.com/spreadsheets/d/1bXkHu98EX6Tqkhz_9CSaopST27jZd9YvcotFTmJYl5o/export?gid=0&format=csv')));
    }
    $result = $dbconn->query("select f from json where providers = '$pro' and ready = 1 order by seq desc limit 1");
    foreach ($result as $line) {
      $json = json_decode(file_get_contents($line[0]),true);
      foreach ($json as $j) {
        $descr = preg_replace("/[^a-z0-9.]+/i","",$j['properties']['descr']);
        $j['properties']['dataurl'] = sprintf(
           "http://data.glos.us/portal/getObsByPlatform.php?provider=%s&platform=%s"
          ,$p
          ,urlencode($j['properties']['descr'])
        );
        if (array_key_exists($p,$cat_csv) && array_key_exists($descr,$cat_csv[$p])) {
          $j['properties']['categories'] = array();
          foreach ($cat_csv[$p][$descr] as $k => $v) {
            $a = explode('CAT ',$k);
            if (count($a) == 2 && $v != '') {
              array_push($j['properties']['categories'],$a[1]);
            }
          }
          if ($cat_csv[$p][$descr]['Abstract'] != '') {
            $j['properties']['abstract'] = $cat_csv[$p][$descr]['Abstract'];
          }
          $j['properties']['keywords']    = explode('|',$cat_csv[$p][$descr]['Keywords']);
          $j['properties']['imageurl']    = $cat_csv[$p][$descr]['Image'];
          $j['properties']['sourcedescr'] = $cat_csv[$p][$descr]['Source (organization)'];
          $j['properties']['infourl']     = $cat_csv[$p][$descr]['GN permalink'];
          if ($cat_csv[$p][$descr]['Alternate Name'] != '') {
            $j['properties']['descr'] = $cat_csv[$p][$descr]['Alternate Name'];
          }
        }
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
    ,'ImageURL'
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
      ,'keywords'     => $p['keywords']
      ,'parameters'   => array_keys($p['topObs'])
      ,'categories'   => $p['categories']
      ,'provider_url' => $p['url']
      ,'data_url'     => $p['dataurl']
      ,'image_url'    => $p['imageurl']
      ,'source_name'  => $p['sourcedescr']
      ,'info_url'     => $p['infourl']
    ));
  }

  echo json_encode(array_slice($d,1));

  $fp = fopen('/tmp/json.csv','w');
  foreach ($d as $fields) {
    fputcsv($fp,$fields);
  }
  fclose($fp);

  function csv_to_array($input) {
    $header  = null;
    $data    = array();
    foreach ($input as $csvLine) {
      if (is_null($header)) {
        $header = str_getcsv($csvLine,',','"');
      }
      else {
        $items = str_getcsv($csvLine,',','"');
        for ($n = 1,$m = count($header); $n < $m; $n++) {
          if ($header[$n] != '') {
            $prepareData[$header[$n]] = $items[$n];
          }
        }
        $data[preg_replace("/[^a-z0-9.]+/i","",$items[0])] = $prepareData;
      }
    }
    return $data;
  }
?>
