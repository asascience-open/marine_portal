<?php
  header('Content-type:application/json');

  include_once('util.php');
  include_once('searchObs.php');

  $getObs = array(
     '/home/cgalvarino/Temp/winds2.xml'
    ,'/home/cgalvarino/Temp/air_temperature.xml'
  );

  $loc = array(0,0);
  $descr = 'My buoy';
  $provider = 'ndbc';
  $organization = 'mine';

  $getObs       = json_decode($_REQUEST['getObs'],true);
  $loc          = json_decode($_REQUEST['location'],true);
  $descr        = $_REQUEST['description'];
  $provider     = $_REQUEST['provider'];
  $organization = $_REQUEST['organization'];
  $siteType     = $_REQUEST['siteType'];
  $url          = $_REQUEST['url'];

  echo json_encode(getSearchObs($getObs,$loc,$descr,$provider,$organization,$siteType,$url));
?>
