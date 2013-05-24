<?php
  date_default_timezone_set('UTC');

  $x       = $_REQUEST['x'];
  $y       = $_REQUEST['y'];
  $w       = $_REQUEST['w'];
  $h       = $_REQUEST['h'];
  $bbox    = $_REQUEST['bbox'];
  $srs     = $_REQUEST['srs'];
  $lon     = $_REQUEST['lon'];
  $lat     = $_REQUEST['lat'];
  $maps    = json_decode($_REQUEST['maps'],true);
  $allMaps = $_REQUEST['allMaps'];
  $dt      = $_REQUEST['dt'];
  $tSpan = date('Y-m-d\T00:00:00',time() - $dt * 24 * 3600).'/'.date('Y-m-d\T00:00:00',time() + 7 * 24 * 3600);

  $gfi = array();
  $requests = array();
  include_once('config/'.getenv('config').'.php');
  header('Content-type: application/json');

  foreach ($gfi as $k => $v) {
    // see if this is something that has a GFI, and if it does, make sure our lon,lat falls w/i any known bbox
    if (
      count(preg_grep('/^'.str_replace('(','\\(',str_replace(')','\\)',$v['map'])).'$/',$maps)) <= 0
      || (array_key_exists('bbox',$v) && !($v['bbox'][0] <= $lon && $lon <= $v['bbox'][2] && $v['bbox'][1] <= $lat && $lat <= $v['bbox'][3]))
    ) {
      continue;
    }
    array_push($requests,$v['u']($srs,$bbox,$x,$y,$w,$h).$tSpan);
    $gfi[$k]['data'] = array();
    if ($v['fmt'] == 'xml') {
      $xml = @simplexml_load_file($v['u']($srs,$bbox,$x,$y,$w,$h).$tSpan);
      if ($xml && $xml->{'Point'}) {
        foreach ($xml->{'Point'} as $p) {
          $a = preg_split("/-| |:/",sprintf("%s",$p->{'Time'}[0]));
          // round to nearest hour
          $t = mktime($a[3],0,0,$a[0],$a[1],$a[2]) + ($a[4] >= 30 ? 3600 : 0);
          if ($t >= time() || $dt > 0) {
            foreach ($p->{'Value'} as $v) {
              $vStr = sprintf("%s",$v->attributes()->{'Var'});
              if (!array_key_exists($vStr,$gfi[$k]['data'])) {
                $gfi[$k]['data'][$vStr] = array(
                   'v' => array()
                  ,'u' => sprintf("%s",$v->attributes()->{'Unit'})
                );
              }
              if (preg_match('/\d/',sprintf("%s",$v))) {
                $gfi[$k]['data'][$vStr]['v'][$t] = sprintf("%f",$v);
              }
            }
          }
        }
      }
      else if ($xml && $xml->{'FeatureInfo'}) {
        $vStr = $v['forceVar']['name'];
        $uom  = $v['forceVar']['uom'];
        foreach ($xml->{'FeatureInfo'} as $p) {
          if (sprintf("%s",$p->{'value'}[0]) != 'none') {
            $t = strtotime($p->{'time'}[0]);
            if ($t >= time() || $dt > 0) {
              if (!array_key_exists($vStr,$gfi[$k]['data'])) {
                $gfi[$k]['data'][$vStr] = array(
                   'v' => array()
                  ,'u' => $uom
                );
              }
              $gfi[$k]['data'][$vStr]['v'][$t] = sprintf("%s",$p->{'value'}[0]);
            }
          }
        }
      }
    }
    else if ($v['fmt'] == 'gml') {
      // gml should only have 1 potential 'vars' -- use it as the key
      $col = '';
      foreach (array_keys($v['vars']) as $colK) {
        $col = $colK;
      }
      $xml = @simplexml_load_file($v['u']($srs,$bbox,$x,$y,$w,$h).$tSpan);
      foreach ($xml->children('http://www.opengis.net/gml')->{'featureMember'} as $featureMember) {
        foreach ($featureMember->children('http://asascience.com/maracoos') as $catch) {
          foreach ($catch->children('http://asascience.com/maracoos') as $details) {
            $vStr = $details->getName();
            $gfi[$k]['data'][$vStr] = array(
               'v' => array(
                 mktime() => sprintf("%s",$catch->children('http://asascience.com/maracoos')->{$col})
               )
            );
          }
        }
      }
    }
    else if ($v['fmt'] == 'csv') {
      $csv = csv_to_array(file_get_contents($v['u']($srs,$bbox,$x,$y,$w,$h).$tSpan));
      for ($i = 0; $i < count($csv); $i++) {
        // round to nearest hour
        preg_match("/(\d\d\d\d)-(\d\d)-(\d\d)T(\d\d):(\d\d):(\d\d)Z/",$csv[$i]['time'],$a);
        $t = mktime($a[4],0,0,$a[2],$a[3],$a[1]) + ($a[4] >= 30 ? 3600 : 0);
        if ($t >= time() || $dt > 0) {
          foreach (array_keys($csv[$i]) as $vStr) {
            preg_match("/(.*)\[(.*)\]/",$vStr,$a);
            if (!array_key_exists($a[1],$gfi[$k]['data'])) {
              $gfi[$k]['data'][$a[1]] = array(
                 'v' => array()
                ,'u' => $a[2]
              );
            }
            $gfi[$k]['data'][$a[1]]['v'][$t] = $csv[$i][$vStr];
          }
        }
      }
    }
  }

  $data = array();
  foreach ($gfi as $u => $v) {
    if (array_key_exists('data',$v)) {
      foreach ($v['data'] as $var => $d) {
        if (array_key_exists($var,$v['vars'])) {
          $vals = array();
          foreach ($d['v'] as $t => $val) {
            $vals[$t] = sprintf(
               $v['vars'][$var]['fmt']
              ,$v['vars'][$var]['f']($val,$v['data'],$t)
            );
          }
          $data[$v['vars'][$var]['name']] = array(
             'u' => $d['u']
            ,'v' => $vals
          );
        }
      }
    }
  }

  $nwsVar2Var = array(
     'Watches, Warnings, and Advisories'      => 'Watches, warnings, and advisories'
    ,'Conditions Icon'                        => 'Conditions'
    ,'Daily Maximum Temperature (Fahrenheit)' => 'Max air temperature (F)'
    ,'Daily Minimum Temperature (Fahrenheit)' => 'Min air temperature (F)'
    ,'Text Forecast'                          => 'Text Forecast'
  );

  $nwsData = array();
  $xml = @simplexml_load_file("http://forecast.weather.gov/MapClick.php?lat=$lat&lon=$lon&FcstType=dwml");
  $d = array('parameters' => array());
  $d['moreWeatherInfoUrl'] = sprintf("%s",$xml->{'data'}[0]->{'moreWeatherInformation'});
  $d['timeLayout'] = array();
  foreach ($xml->{'data'}[0]->{'time-layout'} as $timeLayout) {
    $layoutKey = sprintf("%s",$timeLayout->{'layout-key'}[0]);
    $d['timeLayout'][$layoutKey] = array();
    foreach ($timeLayout->{'start-valid-time'} as $startValidTime) {
      $t = strtotime(sprintf("%s",$startValidTime));
      array_push($d['timeLayout'][$layoutKey],$t);
    }
  }
  foreach ($xml->{'data'}[0]->{'parameters'} as $parameter) {
    foreach ($parameter as $param) {
      $name       = sprintf("%s",$param->{'name'});
      $units      = sprintf("%s",$param->attributes()->{'units'});
      if ($units != '') {
        $units = " ($units)";
      }
      $var = "$name$units";
      if (array_key_exists($var,$nwsVar2Var)) {
        $var = $nwsVar2Var[$var];
      }
      $var = "NWS $var";
      if ($var != 'NWS Watches, warnings, and advisories' || ($var == 'NWS Watches, warnings, and advisories' && !isset($nwsData[$var]))) {
        $nwsData[$var] = array(
           'u' => $units
          ,'v' => array()
        );
      }
      $timeLayout = sprintf("%s",$param->attributes()->{'time-layout'});
      $d["$name$units"] = array();
      $i = 0;
      foreach ($param as $p) {
        // don't save each hazard by time -- save them all by name
        if ($name == 'Watches, Warnings, and Advisories') {
          foreach ($p as $hazard) {
            array_push($nwsData[$var]['v'],array(
               'headline'      => sprintf("%s",$hazard->attributes()->{'headline'})
              ,'hazardTextURL' => sprintf("%s",$hazard->{'hazardTextURL'})
            ));
          }
        }
        else if ($i++ > 0) {
          if ($d['timeLayout'][$timeLayout][$i-2] >= time()) {
            $nwsData[$var]['v'][$d['timeLayout'][$timeLayout][$i-2]] = sprintf("%s",$p);
          }
        }
      }
    }
  }

  foreach ($nwsVar2Var as $k => $v) {
    if (array_key_exists("NWS $v",$nwsData)) {
      $data["NWS $v"] = $nwsData["NWS $v"];
    }
  }

/*
  // get zone fc's
  $gfi = "http://db1.charthorizon.com/races-cgi-bin/mapserv?map=/home/map/mapper/prod/htdocs/nws/zones.map&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=EPSG:3857&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/plain&WIDTH=%d&HEIGHT=%d&INFO_FORMAT=text/plain&QUERY_LAYERS=%s&LAYERS=%s";
  preg_match("/ID = '(.*)'/",file_get_contents(sprintf($gfi,$bbox,$x,$y,$w,$h,'mz','mz')),$matches);
  if (count($matches) == 2) {
    $txt = file_get_contents('http://weather.noaa.gov/pub/data/forecasts/marine/coastal/'.strtolower(substr($matches[1],0,2).'/'.$matches[1].'.txt'));
    if ($txt) {
      $data['NWS Forecast : coastal'] = array(
        'v' => array(
          strtotime(date('Y-m-d\T00:00:00')) => str_replace("\n",'<br>',$txt)
        )
      );
    }
    else {
      $txt =  file_get_contents('http://weather.noaa.gov/pub/data/forecasts/marine/great_lakes/'.strtolower(substr($matches[1],0,2).'/'.$matches[1].'.txt'));
      if ($txt) {
        $data['NWS Forecast : coastal'] = array(
          'v' => array(
            strtotime(date('Y-m-d\T00:00:00')) => str_replace("\n",'<br>',$txt)
          )
        );
      }
    }
  }
  preg_match("/ID = '(.*)'/",file_get_contents(sprintf($gfi,$bbox,$x,$y,$w,$h,'oz','oz')),$matches);
  if (count($matches) == 2) {
    $data['NWS Forecast : offshore'] = array(
      'v' => array(
        strtotime(date('Y-m-d\T00:00:00')) => str_replace("\n",'<br>',file_get_contents('http://weather.noaa.gov/pub/data/forecasts/marine/offshore/'.strtolower(substr($matches[1],0,2).'/'.$matches[1].'.txt')))
      )
    );
  }
*/

  echo json_encode(array(
     'data'     => $data
    ,'allMaps'  => $allMaps == 'true'
    ,'pLonLat'  => $_REQUEST['pLonLat']
    ,'requests' => $requests)
  );

// from http://www.php.net/manual/en/function.str-getcsv.php#104558
function csv_to_array($input,$delimiter=',') {
  $header  = null;
  $data    = array();
  $csvData = str_getcsv($input,"\n");
  foreach ($csvData as $csvLine) {
    if (is_null($header)) {
      $header = explode($delimiter, $csvLine);
    }
    else {
      $items = explode($delimiter, $csvLine);
      for ($n = 0,$m = count($header); $n < $m; $n++) {
        $prepareData[$header[$n]] = $items[$n];
      }
      $data[] = $prepareData;
    }
  }
  return $data;
} 
?>
