<?php
  date_default_timezone_set('UTC');
  session_start(); 
  header("Cache-Control: private, max-age=10800, pre-check=10800");
  header("Pragma: private");
  header("Expires: " . date(DATE_RFC822,strtotime("2 day")));

  $u = $_REQUEST['u'];

  if (!preg_match('/getlegendgraphic/i',$u)) {
    exit;
  }

  if (isset($_REQUEST['GetMetadata'])) {
    $img = '';
    $legend = json_decode($_REQUEST['GetMetadata'],true);
    if ($legend['slope'] == -999) {
      $img = $legend['image'];
    }
    else {
      $barSize = array(30,150);
      $scaleRange = array(0,0);
      if (isset($_REQUEST['COLORSCALERANGE'])) {
        $scaleRange = explode(',',$_REQUEST['COLORSCALERANGE']);
      }
      else {
        $metaU = substr($u,0,strpos($u,'?'))
          .'?item=layerDetails&request=GetMetadata'
          .'&layerName='.$_REQUEST['LAYER']
          .'&TIME='.$_REQUEST['TIME'];
        $json = json_decode(@file_get_contents($metaU));
        $scaleRange = $json->{'scaleRange'};
      }
      $scaleRange[0] = $scaleRange[0] * $legend['slope'] + $legend['offset'];
      $scaleRange[1] = $scaleRange[1] * $legend['slope'] + $legend['offset'];
      $u .= '&COLORBARONLY=true&width='.$barSize[0].'&height='.$barSize[1];

      $img = '/tmp/'.time().rand().'.png';
      $c = file_get_contents($u);
      if ($c === FALSE) {
        $c = file_get_contents('img/warning.png');
        file_put_contents($img,$c);
      }
      else {
        file_put_contents($img,$c);
        $origImg = new Imagick($img);
        $origImg->borderImage('black',1,1);

        $canvas = new Imagick();
        $canvas->newImage($barSize[0] + 75,$barSize[1] + 15,new ImagickPixel('transparent'));
        $canvas->setImageFormat('png');
        $canvas->compositeImage($origImg,imagick::COMPOSITE_OVER,0,10 / 2);

        $draw = new ImagickDraw();
        $draw->setFont('Helvetica');
        $draw->setFontSize(12);
        for ($i = 0; $i <= 1; $i += 0.25) {
          $val = ($scaleRange[1] - $scaleRange[0]) * (1 - $i) + $scaleRange[0];
          $fmt = $legend['format'];
          if (isset($legend['log']) && (0 < $i && $i < 1)) {
            $val = log($val);
            $fmt = '%0.1f';
          }
          $draw->annotation(
             $barSize[0] + 5
            ,$barSize[1] * $i + 10
            ,sprintf(
               $fmt
              ,$val
            )
          );
        }
        $canvas->drawImage($draw);
        $canvas->trimImage(0);
        $canvas->writeImage($img);
      }
    }

    header('Content-type: image/png');
    $c = file_get_contents($img);
    echo $c;
  }
  else {
    $c = @file_get_contents(urldecode($u));
    $content_type = 'Content-Type: text/plain';
    for ($i = 0; $i < count($http_response_header); $i++) {
      if (preg_match('/content-type/i',$http_response_header[$i])) {
        $content_type = $http_response_header[$i];
      }
    }
    if ($c) {
      header($content_type);
      echo $c;
    }
    else {
      header('Content-Type: image/png');
      $c = file_get_contents('img/warning.png');
      echo $c;
    }
  }
?>
