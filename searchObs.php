<?php
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
      $xml = @simplexml_load_file($u);
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
