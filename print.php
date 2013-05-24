<?php
  set_time_limit(90);

  $layers   = json_decode($_REQUEST['lyr'],true);
  $features = json_decode($_REQUEST['ftr'],true);
  $basemap  = json_decode($_REQUEST['bm'],true);
  $legend   = $_REQUEST['leg'];
  $title    = $_REQUEST['title'];
  $baseUrl  = $_REQUEST['base'];
  $t        = $_REQUEST['t'];
  $data     = json_decode($_REQUEST['data'],true);
  $w        = $_REQUEST['w'];
  $h        = $_REQUEST['h'];
  $bbox     = json_decode($_REQUEST['bbox'],true);

  $tmp_dir = getenv('tmpFs').'/';
  $id      = time().'.'.rand();

  $bm = new Imagick();
  $bm->newImage($w,$h,new ImagickPixel('transparent'));
  $bm->setImageFormat('png');
  $j = 0;
  foreach ($basemap as $k => $v) {
    $handle = fopen($tmp_dir.$id.'.'.$j.'.bm.png','w');
    $remote_img = @file_get_contents($basemap[$k]['url']);
    fwrite($handle,$remote_img);
    fclose($handle);
    if (getimagesize($tmp_dir.$id.'.'.$j.'.bm.png')) {
      $img = new Imagick($tmp_dir.$id.'.'.$j++.'.bm.png');
      $bm->compositeImage($img,imagick::COMPOSITE_OVER,$basemap[$k]['x'],$basemap[$k]['y']);
    }
  }

  $static = new Imagick();
  $static->newImage($w,$h,new ImagickPixel('transparent'));
  $static->setImageFormat('png');

  $j = 0;
  foreach ($features as $k => $v) {
    for ($i = 0; $i < count($features[$k]); $i++) {
      $handle = fopen($tmp_dir.$id.$k.'.'.$j.'.gif','w');
      fwrite($handle,file_get_contents(@$features[$k][$i][2]));
      fclose($handle);
      $img = new Imagick($tmp_dir.$id.$k.'.'.$j++.'.gif');
      $img->scaleImage($features[$k][$i][3],$features[$k][$i][4],true);
      $dim = $img->getImageGeometry();
      $static->compositeImage($img,imagick::COMPOSITE_OVER,$features[$k][$i][0] - $dim['width'] / 2,$features[$k][$i][1] - $dim['height'] / 2);
    }
  }

  $olay = new Imagick();
  $olay->newImage($w,$h,new ImagickPixel('transparent'));
  $olay->setImageFormat('png');
  $j = 0;
  foreach ($layers as $k => $v) {
    for ($m = 0; $m < count($layers[$k]); $m++) {
      $handle = fopen($tmp_dir.$id.'.'.$j.'.png','w');
      $remote_img = @file_get_contents($layers[$k][$m]['url']);
      fwrite($handle,$remote_img);
      fclose($handle);
      if (getimagesize($tmp_dir.$id.'.'.$j.'.png')) {
        $img = new Imagick($tmp_dir.$id.'.'.$j++.'.png');
        // leave initially opaque cells alone
        $img->evaluateImage(Imagick::EVALUATE_MULTIPLY,$layers[$k][$m]['opacity'],Imagick::CHANNEL_ALPHA);
        $olay->compositeImage($img,imagick::COMPOSITE_OVER,$layers[$k][$m]['x'],$layers[$k][$m]['y']);
      }
    }
  }

  // final assembly
  $canvas = new Imagick();
  $canvas->newImage($w,$h,new ImagickPixel('transparent'));
  $canvas->setImageFormat('png');
  $canvas->compositeImage($bm,imagick::COMPOSITE_OVER,0,0);
  $canvas->compositeImage($olay,imagick::COMPOSITE_OVER,0,0);
  $canvas->compositeImage($static,imagick::COMPOSITE_OVER,0,0);

  // keymap
  $handle = fopen("$tmp_dir.$id.keymap.png",'w');
  $remote_img = @file_get_contents("https://maps.googleapis.com/maps/api/staticmap?key=AIzaSyBuB8P_e6vQcucjnE64Kh2Fwu6WzhMXZzI&maptype=terrain&zoom=4&path=weight:1|fillcolor:0x0000AA11|color:0x0000FFBB|$bbox[1],$bbox[0]|$bbox[1],$bbox[2]|$bbox[3],$bbox[2]|$bbox[3],$bbox[0]|$bbox[1],$bbox[0]&size=128x128&sensor=false");
  fwrite($handle,$remote_img);
  fclose($handle);
  if (getimagesize("$tmp_dir.$id.keymap.png")) {
    $img = new Imagick("$tmp_dir.$id.keymap.png");
    $img->borderImage(new ImagickPixel('#ffffff'),2,2);
    $canvas->compositeImage($img,imagick::COMPOSITE_OVER,$w - 129,$h - 129);
  }

  // write it
  $canvas->writeImage($tmp_dir.$id.'.print.png');

  $dataJson = json_encode($data);
  $chartJs  = '';
  $chartTr  = '';
  $chartDiv = '';
  if ($data) {
    $chartJs  = <<<EOHEAD
    <script type="text/javascript" src="$baseUrl/print.js?$id"></script>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script>
      var chartData  = $dataJson;
      var chartWidth = $w;
      google.load('visualization', '1.0', {'packages':['corechart']});
      google.setOnLoadCallback(makeCharts);
    </script>
EOHEAD;
    $chartTr  = '<tr><td class="subtitle" style="text-align:center" id="subtitle"></td></tr>';
    $chartDiv = '<div id="charts"></div>';
  }

  $pLonLat = '';
  if ($_REQUEST['pLonLat'] != '') {
    $pLonLat = '<tr><td style="text-align:center;padding-bottom:6px">'.urldecode($_REQUEST['pLonLat']).'</td></tr>';
  }
  $p = <<<EOHTML
<html>
  <head>
    <title>$title</title>
    <link rel="stylesheet" type="text/css" href="$baseUrl/print.css"/>
    $chartJs
  </head>
  <body>
    <table>
      <tr><th style="text-align:center" id="title">$title</th></tr>
      $chartTr
      $pLonLat
      <tr><td style="text-align:center;padding-bottom:6px">Created $t</td></tr>
      <tr><td><img id="map" src="$id.print.png"></td></tr>
      <tr><td>&nbsp;</td></tr>
      <tr><td><table id="legend"><tr><td>$legend</td></tr></table></td></tr>
    </table>
    $chartDiv
  </body>
</html>
EOHTML;

  file_put_contents($tmp_dir.$id.'.php',$p);

  echo "$id.php";
?>
