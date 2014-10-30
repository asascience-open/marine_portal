<?php
  ini_set('memory_limit','256M');
  header('Content-type: application/json');

  $providers = explode(',',getenv('providers'));

  include_once('util.php');
  include_once('searchObs.php');

  date_default_timezone_set('UTC');

  $json = null;
  if (!array_key_exists('fromSearch',$_REQUEST) || (array_key_exists('fromSearch',$_REQUEST) && $_REQUEST['fromSearch'] != 'true')) {
    $data   = array();
    $dbconn = new PDO('sqlite:db/json.sqlite3');
    foreach ($providers as $p) {
      $result = $dbconn->query("select f from json where providers = '$p' and ready = 1 order by seq desc limit 1");
      foreach ($result as $line) {
        $data = array_merge($data,json_decode(file_get_contents($line[0]),true));
      }
    }
    if (count($data) > 0) {
      $json = $data;
    }
  }
  else {
    $minT         = date('Y-m-d\T00:00:00\Z',strtotime($_REQUEST['minT']));
    $maxT         = date('Y-m-d\T00:00:00\Z',strtotime($_REQUEST['maxT']) + 3600 * 24);
    $minTfixed    = date('Y-m-d\T00:00:00\Z',strtotime($_REQUEST['maxT']) - 3600 * 24 * 60);
    if ($_REQUEST['maxT'] == '') {
      $maxT       = date('Y-m-d\T00:00:00\Z');
      $minTfixed  = date('Y-m-d\T00:00:00\Z',time() - 3600 * 24 * 60);

    }
    $getObs       = json_decode($_REQUEST['getObs'],true);
    for ($i = 0; $i < count($getObs); $i++) {
      if (strpos($getObs[$i],'ndbc')) {
        $getObs[$i] = str_replace('latest',"$minTfixed/$maxT",$getObs[$i]);
      }
      else {
        $getObs[$i] = str_replace('latest',"$minT/$maxT",$getObs[$i]);
      }
    }
    $loc          = json_decode($_REQUEST['location'],true);
    $descr        = $_REQUEST['description'];
    $provider     = $_REQUEST['provider'];
    $organization = $_REQUEST['organization'];
    $siteType     = $_REQUEST['siteType'];
    $url          = $_REQUEST['url'];

    $json = getSearchObs($getObs,$loc,$descr,$provider,$organization,$siteType,$url);
  }

  for ($i = 0; $i < count($json); $i++) {
    if ($json[$i]['properties']['provider'] == $_REQUEST['provider'] && $json[$i]['properties']['descr'] == $_REQUEST['descr']) {
      echo json_encode(array(
         't'        => $json[$i]['properties']['timeSeries'][$_REQUEST['varName']]['t']
        ,'v'        => !array_key_exists('Depth',$json[$i]['properties']['timeSeries'][$_REQUEST['varName']]['v'][$_REQUEST['varUnits']])
          ? $json[$i]['properties']['timeSeries'][$_REQUEST['varName']]['v'][$_REQUEST['varUnits']]
          : makeUV($json[$i]['properties']['timeSeries'][$_REQUEST['varName']]['v'])
        ,'id'       => $_REQUEST['id']
        ,'provider' => $_REQUEST['provider']
        ,'descr'    => $_REQUEST['descr']
        ,'varName'  => $_REQUEST['varName']
        ,'varUnits' => $_REQUEST['varUnits']
      ));
      exit;
    } 
  }

  echo json_encode(array(
     't'        => array()
    ,'v'        => array()
    ,'id'       => $_REQUEST['id']
    ,'provider' => $_REQUEST['provider']
    ,'descr'    => $_REQUEST['descr']
    ,'varName'  => $_REQUEST['varName']
    ,'varUnits' => $_REQUEST['varUnits']
  ));

  function makeUV($data) {
    $u = array();
    $v = array();
    $z = array();
    foreach ($data['deg']['Depth']['ft'] as $d => $ts) {
      $u[$d] = array();
      $v[$d] = array();
      array_push($z,$d);
      for ($i = 0; $i < count($ts); $i++) {
        $dir = $ts[$i];
        $spd = $data['knots']['Depth']['ft'][$d][$i];
        array_push($u[$d],-$spd * sin(deg2rad($dir)));
        array_push($v[$d],-$spd * cos(deg2rad($dir)));
      }
    }
    return array(
       'u'      => $u
      ,'v'      => $v
      ,'z'      => $z
      ,'zUnits' => 'ft'
    );
  }
?>
