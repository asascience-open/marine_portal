<?php
  header('Content-type:application/json');

  include_once('util.php');
  include_once('searchObs.php');

  $getObs       = array(
    'http://tds.glos.us/thredds/dodsC/MTRI-Ranger3.nc___OPENDAP___air_temperature'
  );
  $loc          = array(0,0);
  $descr        = 'My buoy';
  $provider     = 'ndbc';
  $organization = 'mine';
  $siteType     = '';
  $url          = ''; 

  $getObs       = json_decode($_REQUEST['getObs'],true);
  $loc          = json_decode($_REQUEST['location'],true);
  $descr        = $_REQUEST['description'];
  $provider     = $_REQUEST['provider'];
  $organization = $_REQUEST['organization'];
  $siteType     = $_REQUEST['siteType'];
  $url          = $_REQUEST['url'];

  echo json_encode(getSearchObs($getObs,$loc,$descr,$provider,$organization,$siteType,$url));
?>
