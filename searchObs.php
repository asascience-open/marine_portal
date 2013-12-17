<?php
  date_default_timezone_set('UTC');

  function getSearchObs($getObs,$loc,$descr,$provider,$organization,$siteType,$url) {
    $tUom = 'english';
    $sites = array();

    // get the data
    array_push($sites,array(
       'descr'        => $descr
      ,'provider'     => $provider
      ,'organization' => $organization
      ,'lon'          => $loc[1]
      ,'lat'          => $loc[0]
      ,'timeSeries'   => array()
      ,'topObs'       => array()
      ,'url'          => $url
      ,'siteType'     => $siteType
    ));

    foreach ($getObs as $u) {
      // do a little opendap magic if necessary
      $a = explode('___OPENDAP___',$u);
      $isOpendap = count($a) == 2;
      if ($isOpendap) {
        $u = $a[0];
        $varName = $a[1];
        preg_match("/$varName\[.* = (.*)\]/",file_get_contents($u.'.dds'),$matches);
        $size = $matches[1];
        $inVarBlock  = false;
        $inTimeBlock = false;
        $varUnits;
        $varFillValue;
        $timeUnits;
        foreach (explode("\n",file_get_contents($u.'.das')) as $l) {
          if (preg_match("/$varName \{(.*)/",$l) > 0) {
            $inVarBlock = true;
          }
          else if (preg_match("/time \{(.*)/",$l) > 0) {
            $inTimeBlock = true;
          }
          else if (!isset($varUnits) && $inVarBlock && preg_match("/units \"(.*)\"/",$l,$matches) > 0) {
            $varUnits = $matches[1];
          }
          else if (!isset($varFillValue) && $inVarBlock && preg_match("/_FillValue (.*)/",$l,$matches) > 0) {
            $varFillValue = $matches[1];
          }
          else if (!isset($timeUnits) && $inTimeBlock && preg_match("/units \"(.*)\"/",$l,$matches) > 0) {
            $timeUnits = $matches[1];
          }
        }
        $a = split(' since ',$timeUnits);
        $baseDate = new DateTime($a[1]);
        $interval = $a[0];
        $inVarBlock  = false;
        $inTimeBlock = false;
        $varVals;
        $timeVals;
        // always attempt to pull back the last 200 vals
        foreach (explode("\n",file_get_contents(sprintf(
          "%s.ascii?time[%d:%d],%s[%d:%d]"
          ,$u
          ,$size - 200
          ,$size - 1
          ,$varName
          ,$size - 200
          ,$size - 1
        ))) as $l) {
          if (preg_match("/^$varName\[.*\]/",$l) > 0) {
            $inVarBlock = true;
          }
          else if (preg_match("/^time\[.*\]/",$l) > 0) {
            $inTimeBlock = true;
          }
          else if (!isset($varVals) && $inVarBlock) {
            $varVals = preg_split("/,( )*/",$l);
          }
          else if (!isset($timeVals) && $inTimeBlock) {
            $timeVals = array();
            foreach (preg_split("/,( )*/",$l) as $t) {
              $dt = clone $baseDate;
              // Wow, date intervals and floats do not get along!  So truncate it for now.  :(
              $a = explode('.',sprintf("%f",$t));
              $t = $a[0];
              date_add($dt,date_interval_create_from_date_string(sprintf("%s %s",$t,$interval)));
              array_push($timeVals,date_format($dt,"U"));
            }
          }
        }

        // time is not necessarily sorted nor unique :(
        $d = array();
        for ($j = 0; $j < count($timeVals); $j++) {
          // might as well check for fill value, but it seems some precision is lost, so do a vulgar
          // check for an exponent match -- negative #'s magically pass thru!
          if (floor(log10($varVals[$j])) != floor(log10($varFillValue))) {
            $d[sprintf("%s",$timeVals[$j])] = $varVals[$j]; 
          }
        }
        ksort($d);
       
        $i = count($sites) - 1;
        foreach ($d as $key => $val) {
          $a = convertUnits(sprintf("%.4f",$val),$varUnits,$tUom == 'english');
          $k = $varName;
          if (!array_key_exists($k,$sites[$i]['timeSeries'])) {
            $v = array(
              $a[0]['uom'] => array()
            );
            if (count($a) == 2) {
              $v[$a[1]['uom']] = array();
            }
            $sites[$i]['timeSeries'][$k] = array(
               'v' => $v
              ,'t' => array()
            );
            $sites[$i]['topObs'][$k] = array(
               'v' => array()
              ,'t' => 0
            );
          }
          array_push($sites[$i]['timeSeries'][$k]['v'][$a[0]['uom']],$a[0]['val']);
          if (count($a) == 2) {
            array_push($sites[$i]['timeSeries'][$k]['v'][$a[1]['uom']],$a[1]['val']);
          }
          array_push($sites[$i]['timeSeries'][$k]['t'],$key);
          if ($key >= $sites[$i]['topObs'][$k]['t']) {
            $sites[$i]['topObs'][$k]['t'] = $key;
            $sites[$i]['topObs'][$k]['v'][$a[0]['uom']] = $a[0]['val'];
            if (count($a) == 2) {
              $sites[$i]['topObs'][$k]['v'][$a[1]['uom']] = $a[1]['val'];
            }
          }
        }
      }

      $xml = !$isOpendap ? @simplexml_load_file($u) : FALSE;
      if ($xml !== FALSE && $xml->children('http://www.opengis.net/om/1.0')->{'member'} && $xml->children('http://www.opengis.net/om/1.0')->{'member'}[0]->children('http://www.opengis.net/om/1.0')) {
        // record time bounds in case this is a latest query
        $endT = sprintf("%s",$xml
          ->children('http://www.opengis.net/om/1.0')->{'member'}[0]
          ->children('http://www.opengis.net/om/1.0')->{'Observation'}[0]
          ->children('http://www.opengis.net/om/1.0')->{'samplingTime'}[0]
          ->children('http://www.opengis.net/gml')->{'TimePeriod'}[0]
          ->children('http://www.opengis.net/gml')->{'endPosition'}
        );
        $uom = '';
        $fields = array();
        foreach ($xml
          ->children('http://www.opengis.net/om/1.0')->{'member'}[0]
          ->children('http://www.opengis.net/om/1.0')->{'Observation'}[0]
          ->children('http://www.opengis.net/om/1.0')->{'result'}[0]
          ->children('http://www.opengis.net/swe/2.0')->{'DataStream'}[0]
          ->children('http://www.opengis.net/swe/2.0')->{'elementType'}[0]
          ->children('http://www.opengis.net/swe/2.0')->{'DataRecord'}[0]
          ->children('http://www.opengis.net/swe/2.0')->{'field'}
        as $field) {
          if (!$field->xpath(".//swe2:value")) {
            $k = sprintf("%s",$field->attributes()->{'name'});
            $v = '';
            $n = $field->xpath(".//swe2:uom/@code");
            foreach ($field->xpath(".//swe2:uom/@code") as $n) {
              $v = sprintf("%s",$n->{'code'});
            }
            $fields[$k] = $v;
          }
        }
        $value = sprintf("%s",$xml
          ->children('http://www.opengis.net/om/1.0')->{'member'}[0]
          ->children('http://www.opengis.net/om/1.0')->{'Observation'}[0]
          ->children('http://www.opengis.net/om/1.0')->{'result'}[0]
          ->children('http://www.opengis.net/swe/2.0')->{'DataStream'}[0]
          ->children('http://www.opengis.net/swe/2.0')->{'values'}
        );
        $i = count($sites) - 1;

        $values = explode("\n",$value);
        if (count($values) >= 250) {
          $v = array();
          foreach (array_rand($values,250) as $k) {
            array_push($v,$values[$k]);
          }
          $values = $v;
        }

        foreach ($values as $line) {
          $p = explode(',',$line);
          if (count($p) == 1) {
            continue;
          }
          $t = strtotime($endT);
          $j = 0;
          foreach ($fields as $k => $v) {
            if ($k == 'time') {
              $t = strtotime($p[$j]); 
            }
            $j++;
          }
          $j = 0;
          foreach ($fields as $k => $v) {
            if ($v == '') {
              $j++;
              continue;
            }
            if ($p[$j] != '') {
              $a = convertUnits($p[$j],$v,$tUom == 'english');
              if (!array_key_exists($k,$sites[$i]['timeSeries'])) {
                $v = array(
                  $a[0]['uom'] => array()
                );
                if (count($a) == 2) {
                  $v[$a[1]['uom']] = array();
                }
                $sites[$i]['timeSeries'][$k] = array(
                   'v' => $v
                  ,'t' => array()
                );
                $sites[$i]['topObs'][$k] = array(
                   'v' => array()
                  ,'t' => 0
                );
              }
              array_push($sites[$i]['timeSeries'][$k]['v'][$a[0]['uom']],$a[0]['val']);
              if (count($a) == 2) {
                array_push($sites[$i]['timeSeries'][$k]['v'][$a[1]['uom']],$a[1]['val']);
              }
              array_push($sites[$i]['timeSeries'][$k]['t'],$t);
              if ($t >= $sites[$i]['topObs'][$k]['t']) {
                $sites[$i]['topObs'][$k]['t'] = $t;
                $sites[$i]['topObs'][$k]['v'][$a[0]['uom']] = $a[0]['val'];
                if (count($a) == 2) {
                  $sites[$i]['topObs'][$k]['v'][$a[1]['uom']] = $a[1]['val'];
                }
              }
            }
            $j++;
          }
        }
      }
    }

    $features = array();
    for ($i = 0; $i < count($sites); $i++) {
      array_push($features,array(
         'type'     => 'Feature'
        ,'geometry' => array(
           'type'        => 'Point'
          ,'coordinates' => array(
             $sites[$i]['lon']
            ,$sites[$i]['lat']
          )
        )
        ,'properties'  => array(
           'descr'      => $sites[$i]['descr']
          ,'lon'        => $sites[$i]['lon']
          ,'lat'        => $sites[$i]['lat']
          ,'timeSeries' => !empty($sites[$i]['timeSeries']) ? $sites[$i]['timeSeries'] : null
          ,'topObs'     => !empty($sites[$i]['topObs']) ? $sites[$i]['topObs'] : null
          ,'url'        => $sites[$i]['url']
          ,'siteType'   => $sites[$i]['siteType']
          ,'provider'   => $sites[$i]['organization'] != '' ? $sites[$i]['organization'] : $sites[$i]['provider']
        )
      ));
    }

    return $features;
  }
?>
