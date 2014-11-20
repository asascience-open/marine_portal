<?php
  date_default_timezone_set('UTC');

  $title     = 'Great Lakes Observing System Data Portal';
  $googleId  = 'UA-32882439-1';

  $hasHelp = true;

  $viewer = 'standard';

  $mapCenter = '-84.4,44.0';
  $mapZoom   = 6;
  $basemap   = 'ESRI Ocean';
  $mode      = 'observations';

  $search        = 'on';
  $tbar          = 'on';
  $byCatch       = 'off';
  $chat          = 'off';
  $bathyContours = 'off';

  $weatherTab = 'Satellite';

  $dataBbox = '-93,40,-75,50';
  $buffer   = "xml/buffer.$dataBbox.json";

  $bannerHeight    = 75;
  $bannerHtml      = <<< EOHTML
<div id="headwrapper"><div id="head"><a href="http://glos.us/"><img src="img/blank.png" width="168" height="75" alt="GLOS" /></a><a href="http://explorer.glos.us/"><img src="img/blank.png" width="632" height="75" alt="GLOS Observations Explorer" /></a> </div><div id="header_alert"></div></div>
EOHTML;
  $southPanelHeight    = 119;
  $southPanelHtml      = <<< EOHTML
<div class="footerbox"> <p><strong>Disclaimer:</strong> All products published on this website are prototype products and are not intended to be used for navigational or operational purposes. <a href="http://glos.us/legal/">View full disclaimer.</a></p> <p style="margin-top:5px"></p><div style="margin-top:5px;padding-bottom:15px; border:none; float:left; padding-right:10px;"><a href="mailto:dmac@glos.us?subject=Data%20Portal%20Feedback"><img src="img/glos/feedback.png" alt="Send feedback" style="border:none"/></a></div> <p class="glosinfo">&copy; Great Lakes Observing System<br /> 229 Nickels Arcade <br /> Ann Arbor, MI 48104<br /> </p> </div>
EOHTML;

  $forecastFooter      = <<< EOHTML
The <a target=_blank href="http://glos.us/data-tools/point-query-tool-glcfs">Point Query Tool</a> and the <a target=_blank href="http://glos.us/data-tools/huron-erie-connecting-waterways-forecasting-system">Huron-Erie Corridor Waterways Forecast System</a> provide additional access to model results.
EOHTML;

  $catalogQueryXML = json_encode(file_get_contents('https://raw.github.com/asascience-open/glos_catalog/master/queries/full_search.xml'));

  $obsLegendsPath = 'img/glos/';
  $obsCptRanges   = array(
     'winds'      => '0,30'
    ,'waves'      => '0,10'
    ,'watertemp'  => '40,80'
    ,'waterlevel' => '550,650'
  );

  $minZoom = array(
     'winds'      => 7
    ,'waves'      => 5
    ,'waterTemp'  => 7
    ,'waterLevel' => 7
    ,'streamflow' => 9
  );

  // don't show the following obs until the zoom = the zoomLevel
  // provider,activeWeatherStations.?,obsFunction,ifOther->topObsName,,ofOther->units,zoomLevel
  $obsCull = array (
     array('NWS' ,'winds'         ,'getWinds'    ,''              ,'' ,8)
    ,array('NWS' ,'airTemperature','getOtherObs' ,'AirTemperature','F',8)
    ,array('NWS' ,'all'           ,'getAllObs'   ,''              ,'' ,8)
    ,array('USGS','waterTemp'     ,'getWaterTemp',''              ,'' ,8)
    ,array('USGS','all'           ,'getAllObs'   ,''              ,'' ,8)
  );

  $defaultObs = 'WaterTemp';
  $defaultFC  = 'off';
  $defaultWWA = 'off';

  $availableObs = array('Winds','Waves','WaterTemp','WaterLevel');

  $chl = getChlorophyllTime();

  $greatLakesJSON = file_get_contents('xml/glosGreatLakes.json');
  $extraInitJS = <<< EOJS
    var features = new OpenLayers.Format.GeoJSON().read($greatLakesJSON);
    for (var i = 0; i < features.length; i++) {
      features[i].geometry.transform(proj4326,proj900913);
    }
    geo.greatLakes = new OpenLayers.Layer.Vector();
    geo.greatLakes.addFeatures(features);
    // map.addLayer(geo.greatLakes);

    if (!cp.get('hideSplashOnStartupCheckbox')) {
      showSplash();
    }
EOJS;

  $fcSliderIncrement = 6;

  $getCapsInfo = array(
     'Currents-GLERL-LakeStClaire'    => getGetCapsInfo('Currents-GLERL-LakeStClaire')
    ,'Currents-GLERL-StLawrenceRiver' => getGetCapsInfo('Currents-GLERL-StLawrenceRiver')
    ,'ecop'                           => getGetCapsInfo('ecop')
  );

  $gfi = array(
    array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_ERIE_ICE_THICKNESS'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_ERIE_ICE_THICKNESS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLECUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Ice concentration' => array(
           'name'    => 'Ice thickness (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Ice thickness (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_HURON_ICE_THICKNESS'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_HURON_ICE_THICKNESS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLHCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Ice concentration' => array(
           'name'    => 'Ice thickness (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Ice thickness (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_MICHIGAN_ICE_THICKNESS'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_MICHIGAN_ICE_THICKNESS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLMCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Ice concentration' => array(
           'name'    => 'Ice thickness (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Ice thickness (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_ONTARIO_ICE_THICKNESS'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_ONTARIO_ICE_THICKNESS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLOCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Ice concentration' => array(
           'name'    => 'Ice thickness (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Ice thickness (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_SUPERIOR_ICE_THICKNESS'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_SUPERIOR_ICE_THICKNESS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLSCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Ice concentration' => array(
           'name'    => 'Ice thickness (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Ice thickness (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_ERIE_ELEVATION'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_ERIE_ELEVATION'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLECUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Elevation' => array(
           'name'    => 'Water level (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Water level (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_HURON_ELEVATION'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_HURON_ELEVATION'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLHCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Elevation' => array(
           'name'    => 'Water level (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Water level (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_MICHIGAN_ELEVATION'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_MICHIGAN_ELEVATION'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLMCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Elevation' => array(
           'name'    => 'Water level (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Water level (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_ONTARIO_ELEVATION'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_ONTARIO_ELEVATION'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLOCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Elevation' => array(
           'name'    => 'Water level (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Water level (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_SUPERIOR_ELEVATION'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_SUPERIOR_ELEVATION'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLSCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Elevation' => array(
           'name'    => 'Water level (m)'
          ,'fmt'     => "%0.2f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Water level (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_ERIE_WAVE_HEIGHT'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_ERIE_WAVE_HEIGHT'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLECUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Height of Combined Winds, Waves, and Swells' => array(
           'name'    => 'Wave height (m)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Waves (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_HURON_WAVE_HEIGHT'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_HURON_WAVE_HEIGHT'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLHCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Height of Combined Winds, Waves, and Swells' => array(
           'name'    => 'Wave height (m)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Waves (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_MICHIGAN_WAVE_HEIGHT'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_MICHIGAN_WAVE_HEIGHT'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLMCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Height of Combined Winds, Waves, and Swells' => array(
           'name'    => 'Wave height (m)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Waves (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_ONTARIO_WAVE_HEIGHT'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_ONTARIO_WAVE_HEIGHT'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLOCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Height of Combined Winds, Waves, and Swells' => array(
           'name'    => 'Wave height (m)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Waves (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'LAKE_SUPERIOR_WAVE_HEIGHT'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'LAKE_SUPERIOR_WAVE_HEIGHT'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLSCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Height of Combined Winds, Waves, and Swells' => array(
           'name'    => 'Wave height (m)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Waves (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'NAM_WINDS'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'NAM_WINDS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['NAM_WINDS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
        'Wind Velocity' => array(
           'name' => 'Wind speed (kts)'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction'     => array(
           'name' => 'Wind direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Winds (NAM)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://wms.glos.us/wms/HECWFS_Latest_Forecast/?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/csv&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'u,v'
          ,'vectors_average_jet_0_1.5_cell_True'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'u,v'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['Currents-GLERL-LakeStClaire']['u,v']['bbox'])
      ,'fmt' => 'csv'
      ,'vars' => array(
        // trick the GFI into thinking 'u' is water speed
        'u' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            $vec_u = $val;
            $vec_v = $a['v']['v'][$t];
            $m = sqrt(pow($vec_u,2) + pow($vec_v,2));
            return $m * 1.94384;
          }
        )
        // trick the GFI into thinking 'v' is water direction
        ,'v' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            $vec_v = $val;
            $vec_u = $a['u']['v'][$t];
            $d = rad2deg(atan2($vec_u,$vec_v));
            return $d + ($d < 0 ? 360 : 0);
          }
        )
      )
      ,'map' => 'Currents (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://wms.glos.us/wms/SLRFVM_Latest_Forecast/?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/csv&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'u,v'
          ,'vectors_average_jet_0_1.5_cell_True'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'u,v'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['Currents-GLERL-StLawrenceRiver']['u,v']['bbox'])
      ,'fmt' => 'csv'
      ,'vars' => array(
        // trick the GFI into thinking 'u' is water speed
        'u' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            $vec_u = $val;
            $vec_v = $a['v']['v'][$t];
            $m = sqrt(pow($vec_u,2) + pow($vec_v,2));
            return $m * 1.94384;
          }
        )
        // trick the GFI into thinking 'v' is water direction
        ,'v' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            $vec_v = $val;
            $vec_u = $a['u']['v'][$t];
            $d = rad2deg(atan2($vec_u,$vec_v));
            return $d + ($d < 0 ? 360 : 0);
          }
        )
      )
      ,'map' => 'Currents (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'GLERLECUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'GLERLECUR_CURRENTS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLECUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'GLERLHCUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'GLERLHCUR_CURRENTS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLHCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'GLERLMCUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'GLERLMCUR_CURRENTS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLMCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'GLERLOCUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'GLERLOCUR_CURRENTS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLOCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'GLERLSCUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'GLERLSCUR_CURRENTS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['GLERLSCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (GLERL)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'NOSERIECUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'NOSERIECUR_CURRENTS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['NOSERIECUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (NOS)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'NOSHURCUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'NOSHURCUR_CURRENTS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['NOSHURCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (NOS)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'NOSMICHCUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'NOSMICHCUR_CURRENTS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['NOSMICHCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (NOS)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'NOSONTCUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'NOSONTCUR_CURRENTS'
        );
      }
      ,'bbox' => explode(',',$getCapsInfo['ecop']['NOSONTCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (NOS)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'NOSSUPCUR_CURRENTS'
          ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'NOSSUPCUR_CURRENTS'
        );
      } 
      ,'bbox' => explode(',',$getCapsInfo['ecop']['NOSSUPCUR_CURRENTS']['bbox'])
      ,'fmt'  => 'xml'
      ,'vars' => array(
         'Water Velocity' => array(
           'name'    => 'Water speed (kts)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (NOS)'
    )
  );

  // ['panel','type','id','getMapUrl','getMapLayers','styles','format','timeParam','opacity','visibility','singleTile','moreInfo','bbox','legend']
  $mapLayersStoreDataJS = "[
    [
       'forecasts'
      ,'wms'
      ,'Winds'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'NAM_WINDS'
      ,'WINDS_VERY_SPARSE_GRADIENT-False-1-0-45-Low'
      ,'image/png'
      ,true
      ,0.5
      ,false
      ,true
      ,'The NAM model is a regional mesoscale data assimilation and forecast model system based on the WRF common modeling infrastructure, currently running at 12 km resolution and 60 layers. NAM forecasts are produced every six hours at 00, 06, 12 and 18 UTC. The NAM graphics are available at three hour increments out to 84 hours. The NAM has non-hydrostatic dynamics and a full suite of physical parameterizations and a land surface model.'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-GLERL-StLawrenceRiver'
      ,'http://wms.glos.us/wms/SLRFVM_Latest_Forecast/'
      ,'u,v'
      ,'vectors_average_jet_0_1.5_cell_True'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'Information currently unavailable'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-GLERL-LakeStClaire'
      ,'http://wms.glos.us/wms/HECWFS_Latest_Forecast/'
      ,'u,v'
      ,'vectors_average_jet_0_1.5_cell_True'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'Information currently unavailable'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-GLERL-Erie'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'GLERLECUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'Information currently unavailable'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-GLERL-Huron'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'GLERLHCUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-GLERL-Michigan'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'GLERLMCUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-GLERL-Ontario'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'GLERLOCUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-GLERL-Superior'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'GLERLSCUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-NOS-Erie'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'NOSERIECUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'The NOAA Great Lakes Operational Forecast System (GLOFS) uses near-real-time atmospheric observations and numerical weather prediction forecast guidance to produce three-dimensional forecasts of water temperature and currents, and two-dimensional forecasts of water levels of the Great Lakes.'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-NOS-Huron'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'NOSHURCUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-NOS-Michigan'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'NOSMICHCUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-NOS-Ontario'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'NOSONTCUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents-NOS-Superior'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'NOSSUPCUR_CURRENTS'
      ,'CURRENTS_RAMP-Jet-1-1-True-0-1-High'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'SuspendedMinerals-LakeErie'
      ,'http://tds.glos.us/thredds/wms/SM/LakeErieSM-Agg?GetMetadata=1&COLORSCALERANGE=0,3&'
      ,'sm'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'SuspendedMinerals-LakeHuron'
      ,'http://tds.glos.us/thredds/wms/SM/LakeHuronSM-Agg?GetMetadata=1&COLORSCALERANGE=0,3&'
      ,'sm'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'SuspendedMinerals-LakeMichigan'
      ,'http://tds.glos.us/thredds/wms/SM/LakeMichiganSM-Agg?GetMetadata=1&COLORSCALERANGE=0,3&'
      ,'sm'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,'<a target=_blank href=\"http://www.mtri.org\"><img width=153 height=100 src=\"img/glos/mtri.png\" style=\"margin-top:5px;border:1px solid #8CA4CB;float:right\" title=\"MTRI\"></a> Water Color of inland and coastal zones results mainly from three different parameters known as color-producing agents (CPAs). Suspended Minerals (SM)  consist of inorganic particulate matter (such as sand, silt, and clay), which scatter and absorb light.  For more information, please visit <a target=_blank href=\"http://www.mtri.org\">www.mtri.org</a> and <a target=_blank href=\"http://www.greatlakesremotesensing.org\">www.greatlakesremotesensing.org</a> - data are provided by the Michigan Tech Research Institute (MTRI). Or visit the <a target=_blank href=\"http://glos.us/projects/observations\">GLOS projects page</a> for more information.'
      ,false
      ,{slope : 1,offset : 0,format : '%.2f'}
    ]
    ,[
       'weather'
      ,'wms'
      ,'SuspendedMinerals-LakeOntario'
      ,'http://tds.glos.us/thredds/wms/SM/LakeOntarioSM-Agg?GetMetadata=1&COLORSCALERANGE=0,3&'
      ,'sm'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'SuspendedMinerals-LakeSuperior'
      ,'http://tds.glos.us/thredds/wms/SM/LakeSuperiorSM-Agg?GetMetadata=1&COLORSCALERANGE=0,3&'
      ,'sm'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'DissolvedOrganicCarbon-LakeErie'
      ,'http://tds.glos.us/thredds/wms/DOC/LakeErieDOC-Agg?GetMetadata=1&COLORSCALERANGE=0,10&'
      ,'doc'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'DissolvedOrganicCarbon-LakeHuron'
      ,'http://tds.glos.us/thredds/wms/DOC/LakeHuronDOC-Agg?GetMetadata=1&COLORSCALERANGE=0,10&'
      ,'doc'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'DissolvedOrganicCarbon-LakeMichigan'
      ,'http://tds.glos.us/thredds/wms/DOC/LakeMichiganDOC-Agg?GetMetadata=1&COLORSCALERANGE=0,10&'
      ,'doc'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,'<a target=_blank href=\"http://www.mtri.org\"><img width=153 height=100 src=\"img/glos/mtri.png\" style=\"margin-top:5px;border:1px solid #8CA4CB;float:right\" title=\"MTRI\"></a> Water Color of inland and coastal zones results mainly from three different parameters known as color-producing agents (CPAs).  Dissolved Organic Carbon (DOC) is a form of organic carbon that is produced during micro-organism metabolism and may also be transported from decaying vegetation via rivers and streams. DOC only absorbs light, it does not scatter light, and appears yellow to brown in color. A significant fraction of the total DOC concentration is invisible and must be estimated empirically from the colored portion known as Colored Dissolved Organic Carbon (CDOM).  For more information, please visit <a target=_blank href=\"http://www.mtri.org\">www.mtri.org</a> and <a target=_blank href=\"http://www.greatlakesremotesensing.org\">www.greatlakesremotesensing.org</a> - data are provided by the Michigan Tech Research Institute (MTRI). Or visit the <a target=_blank href=\"http://glos.us/projects/observations\">GLOS projects page</a> for more information.'
      ,false
      ,{slope : 1,offset : 0,format : '%d'}
    ]
    ,[
       'weather'
      ,'wms'
      ,'DissolvedOrganicCarbon-LakeOntario'
      ,'http://tds.glos.us/thredds/wms/DOC/LakeOntarioDOC-Agg?GetMetadata=1&COLORSCALERANGE=0,10&'
      ,'doc'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'DissolvedOrganicCarbon-LakeSuperior'
      ,'http://tds.glos.us/thredds/wms/DOC/LakeSuperiorDOC-Agg?GetMetadata=1&COLORSCALERANGE=0,10&'
      ,'doc'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'ColoredDissolvedOrganicMatter-LakeErie'
      ,'http://tds.glos.us/thredds/wms/CDOM/LakeErieCDOM-Agg?GetMetadata=1&COLORSCALERANGE=0,2&'
      ,'cdom'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'ColoredDissolvedOrganicMatter-LakeHuron'
      ,'http://tds.glos.us/thredds/wms/CDOM/LakeHuronCDOM-Agg?GetMetadata=1&COLORSCALERANGE=0,2&'
      ,'cdom'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'ColoredDissolvedOrganicMatter-LakeMichigan'
      ,'http://tds.glos.us/thredds/wms/CDOM/LakeMichiganCDOM-Agg?GetMetadata=1&COLORSCALERANGE=0,2&'
      ,'cdom'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,'<a target=_blank href=\"http://www.mtri.org\"><img width=153 height=100 src=\"img/glos/mtri.png\" style=\"margin-top:5px;border:1px solid #8CA4CB;float:right\" title=\"MTRI\"></a> Water Color of inland and coastal zones results mainly from three different parameters known as color-producing agents (CPAs).  Colored Dissolved Organic Material (CDOM) is the optically measurable component of the dissolved organic carbon in water and is naturally occurring due to decaying plant matter and micro-organism metabolism. CDOM is highly absorbent of blue light which results in a yellow-brown color. CDOM is typically delivered to large aquatic ecosystems via river discharge.  For more information, please visit <a target=_blank href=\"http://www.mtri.org\">www.mtri.org</a> and <a target=_blank href=\"http://www.greatlakesremotesensing.org\">www.greatlakesremotesensing.org</a> - data are provided by the Michigan Tech Research Institute (MTRI). Or visit the <a target=_blank href=\"http://glos.us/projects/observations\">GLOS projects page</a> for more information.'
      ,false
      ,{slope : 1,offset : 0,format : '%.1f'}
    ]
    ,[
       'weather'
      ,'wms'
      ,'ColoredDissolvedOrganicMatter-LakeOntario'
      ,'http://tds.glos.us/thredds/wms/CDOM/LakeOntarioCDOM-Agg?GetMetadata=1&COLORSCALERANGE=0,2&'
      ,'cdom'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'ColoredDissolvedOrganicMatter-LakeSuperior'
      ,'http://tds.glos.us/thredds/wms/CDOM/LakeSuperiorCDOM-Agg?GetMetadata=1&COLORSCALERANGE=0,2&'
      ,'cdom'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'WaterSurfaceTemperature-LakeMichigan'
      ,'http://tds.glos.us/thredds/wms/SST/LakeMichiganSST-Agg?GetMetadata=1&COLORSCALERANGE=0,30&'
      ,'sst'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,'<a target=_blank href=\"http://www.mtri.org\"><img width=153 height=100 src=\"img/glos/mtri.png\" style=\"margin-top:5px;border:1px solid #8CA4CB;float:right\" title=\"MTRI\"></a>These data represent night-time temperature data derived from 250-meter resolution MODIS satellite imagery, collected at the 4-micron wavelength when cloud cover is not significant. Previous analysis has shown these data to be accurate within approximately 1/2 degree Celsius. For more information, please visit <a target=_blank href=\"http://www.mtri.org\">www.mtri.org</a> and <a target=_blank href=\"http://www.greatlakesremotesensing.org\">www.greatlakesremotesensing.org</a> - data are provided by the Michigan Tech Research Institute (MTRI). Or visit the <a target=_blank href=\"http://glos.us/projects/observations\">GLOS projects page</a> for more information.'
      ,false
      ,{slope : 9/5,offset : 32,format : '%d'}
    ]
    ,[
       'weather'
      ,'wms'
      ,'WaterSurfaceTemperature-LakeErie'
      ,'http://tds.glos.us/thredds/wms/SST/LakeErieSST-Agg?GetMetadata=1&COLORSCALERANGE=0,30&'
      ,'sst'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,{slope : 9/5,offset : 32,format : '%d'}
    ]
    ,[
       'weather'
      ,'wms'
      ,'WaterSurfaceTemperature-LakeHuron'
      ,'http://tds.glos.us/thredds/wms/SST/LakeHuronSST-Agg?GetMetadata=1&COLORSCALERANGE=0,30&'
      ,'sst'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,{slope : 9/5,offset : 32,format : '%d'}
    ]
    ,[
       'weather'
      ,'wms'
      ,'WaterSurfaceTemperature-LakeOntario'
      ,'http://tds.glos.us/thredds/wms/SST/LakeOntarioSST-Agg?GetMetadata=1&COLORSCALERANGE=0,30&'
      ,'sst'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,{slope : 9/5,offset : 32,format : '%d'}
    ]
    ,[
       'weather'
      ,'wms'
      ,'WaterSurfaceTemperature-LakeSuperior'
      ,'http://tds.glos.us/thredds/wms/SST/LakeSuperiorSST-Agg?GetMetadata=1&COLORSCALERANGE=0,30&'
      ,'sst'
      ,'boxfill/rainbow'
      ,'image/png'
      ,false
      ,1
      ,false
      ,false
      ,''
      ,false
      ,{slope : 9/5,offset : 32,format : '%d'}
    ]
    ,[
       'weather'
      ,'tilecache'
      ,'NaturalColor-LakeMichigan'
      ,'http://tiles.glos.us/tiles/current/'
      ,'michigan-natcolor'
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,'<a target=_blank href=\"http://www.mtri.org\"><img width=153 height=100 src=\"img/glos/mtri.png\" style=\"margin-top:5px;border:1px solid #8CA4CB;float:right\" title=\"MTRI\"></a> Water Color of inland and coastal zones results mainly from three different parameters known as color-producing agents (CPAs).  Collected by the MODIS satellite sensor, these natural color images are produced using the Red, Green, and Blue channels at a spatial resolution of 1 kilometer.  For more information, please visit <a target=_blank href=\"http://www.mtri.org\">www.mtri.org</a> and <a target=_blank href=\"http://www.greatlakesremotesensing.org\">www.greatlakesremotesensing.org</a> - data are provided by the Michigan Tech Research Institute (MTRI). Or visit the <a target=_blank href=\"http://glos.us/projects/observations\">GLOS projects page</a> for more information.'
      ,false
      ,{slope : -999,offset : -999,format : '',image : 'img/blank1x1.png'}
    ]
    ,[
       'weather'
      ,'tilecache'
      ,'NaturalColor-LakeErie'
      ,'http://tiles.glos.us/tiles/current/'
      ,'erie-natcolor'
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'tilecache'
      ,'NaturalColor-LakeSuperior'
      ,'http://tiles.glos.us/tiles/current/'
      ,'superior-natcolor'
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'tilecache'
      ,'NaturalColor-LakeHuron'
      ,'http://tiles.glos.us/tiles/current/'
      ,'huron-natcolor'
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'tilecache'
      ,'NaturalColor-LakeOntario'
      ,'http://tiles.glos.us/tiles/current/'
      ,'ontario-natcolor'
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'Chlorophyll-LakeMichigan'
      ,'http://geoserver2.mtri.org/geoserver/WaterRemoteSensing/wms?sld=' + encodeURIComponent(
        'http://72.44.60.22/glos/sld/glosChlorophyllSld.php?REGION=Small&LAYER='
        + 'CHLLOW_Michigan_".$chl['times']['Lake Michigan']."'
      )
      ,''
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,'<a target=_blank href=\"http://www.mtri.org\"><img width=153 height=100 src=\"img/glos/mtri.png\" style=\"margin-top:5px;border:1px solid #8CA4CB;float:right\" title=\"MTRI\"></a> Water Color of inland and coastal zones results mainly from three different parameters known as color-producing agents (CPAs).  Chlorophyll (Chl) is a green pigment found in plant and phytoplankton cells.  Algal cells that are suspended in water produce a green-yellow color.  For more information, please visit <a target=_blank href=\"http://www.mtri.org\">www.mtri.org</a> and <a target=_blank href=\"http://www.greatlakesremotesensing.org\">www.greatlakesremotesensing.org</a> - data are provided by the Michigan Tech Research Institute (MTRI). Or visit the <a target=_blank href=\"http://glos.us/projects/observations\">GLOS projects page</a> for more information.'
      ,false
      ,{slope : 1,offset : 0,format : '%d',log : true}
    ]
    ,[
       'weather'
      ,'wms'
      ,'Chlorophyll-LakeErie'
      ,'http://geoserver2.mtri.org/geoserver/WaterRemoteSensing/wms?sld=' + encodeURIComponent(
        'http://72.44.60.22/glos/sld/glosChlorophyllSld.php?REGION=Big&LAYER='
        + 'CHL_Erie_".$chl['times']['Lake Erie']."'
      )
      ,''
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'Chlorophyll-LakeHuron'
      ,'http://geoserver2.mtri.org/geoserver/WaterRemoteSensing/wms?sld=' + encodeURIComponent(
        'http://72.44.60.22/glos/sld/glosChlorophyllSld.php?REGION=Small&LAYER='
        + 'CHLLOW_Huron_".$chl['times']['Lake Huron']."'
      )
      ,''
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'Chlorophyll-LakeOntario'
      ,'http://geoserver2.mtri.org/geoserver/WaterRemoteSensing/wms?sld=' + encodeURIComponent(
        'http://72.44.60.22/glos/sld/glosChlorophyllSld.php?REGION=Big&LAYER='
        + 'CHL_Ontario_".$chl['times']['Lake Ontario']."'
      )
      ,''
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'Chlorophyll-LakeSuperior'
      ,'http://geoserver2.mtri.org/geoserver/WaterRemoteSensing/wms?sld=' + encodeURIComponent(
        'http://72.44.60.22/glos/sld/glosChlorophyllSld.php?REGION=Small&LAYER='
        + 'CHLLOW_Superior_".$chl['times']['Lake Superior']."'
      )
      ,''
      ,''
      ,'image/png'
      ,false
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'IceThickness-GLERL-Erie'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_ERIE_ICE_THICKNESS'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'Information currently unavailable'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'IceThickness-GLERL-Huron'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_HURON_ICE_THICKNESS'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'IceThickness-GLERL-Michigan'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_MICHIGAN_ICE_THICKNESS'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'IceThickness-GLERL-Ontario'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_ONTARIO_ICE_THICKNESS'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'IceThickness-GLERL-Superior'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_SUPERIOR_ICE_THICKNESS'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'WaterLevel-GLERL-Erie'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_ERIE_ELEVATION'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'Information currently unavailable'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'WaterLevel-GLERL-Huron'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_HURON_ELEVATION'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'WaterLevel-GLERL-Michigan'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_MICHIGAN_ELEVATION'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'WaterLevel-GLERL-Ontario'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_ONTARIO_ELEVATION'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'WaterLevel-GLERL-Superior'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_SUPERIOR_ELEVATION'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Waves-GLERL-Erie'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_ERIE_WAVE_HEIGHT'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'Information currently unavailble'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Waves-GLERL-Huron'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_HURON_WAVE_HEIGHT'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Waves-GLERL-Michigan'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_MICHIGAN_WAVE_HEIGHT'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Waves-GLERL-Ontario'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_ONTARIO_WAVE_HEIGHT'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Waves-GLERL-Superior'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'LAKE_SUPERIOR_WAVE_HEIGHT'
      ,''
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wunderground'
      ,'Base reflectivity'
      ,'http://api.wunderground.com/api/cd3aefa3b6d50f6a/radar/image.png?GetMetadata=1&smooth=1&rainsnow=1'
      ,''
      ,''
      ,''
      ,false
      ,1
      ,false
      ,true
      ,'NEXRAD (Next Generation Radar) can measure both precipitation and wind. The radar emits a short pulse of energy, and if the pulse strike an object (raindrop, snowflake, bug, bird, etc), the radar waves are scattered in all directions. A small portion of that scattered energy is directed back toward the radar. For more information, read Weather Underground\'s layer details <a target=_blank href=\'http://www.wunderground.com/radar/help.asp\'>here</a>.'
      ,false
      ,{slope : -999,offset : -999,format : '',image : 'http://icons-ak.wxug.com/i/wm/radarLegend.png'}
    ]
  ]
";

  // ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical','conditionsReport','liteLegendLabel','liteLegendImage']
  $forecastMapsStoreDataJS = "[
     ['Currents (GLERL)',['Currents-GLERL-Erie','Currents-GLERL-Huron','Currents-GLERL-Michigan','Currents-GLERL-Ontario','Currents-GLERL-Superior','Currents-GLERL-LakeStClaire','Currents-GLERL-StLawrenceRiver'],['Currents-GLERL-Erie'],false,true,false,false,'','']
    ,['Currents (NOS)',['Currents-NOS-Erie','Currents-NOS-Huron','Currents-NOS-Michigan','Currents-NOS-Ontario','Currents-NOS-Superior'],['Currents-NOS-Erie'],false,false,false,false,'','']
    ,['Ice thickness (GLERL)',['IceThickness-GLERL-Erie','IceThickness-GLERL-Huron','IceThickness-GLERL-Michigan','IceThickness-GLERL-Ontario','IceThickness-GLERL-Superior'],['IceThickness-GLERL-Erie'],['Ice thickness (m)'],false,false,false,'','']
    ,['Water level (GLERL)',['WaterLevel-GLERL-Erie','WaterLevel-GLERL-Huron','WaterLevel-GLERL-Michigan','WaterLevel-GLERL-Ontario','WaterLevel-GLERL-Superior'],['WaterLevel-GLERL-Erie'],['Water level (m)'],false,false,false,'','']
    ,['Waves (GLERL)',['Waves-GLERL-Erie','Waves-GLERL-Huron','Waves-GLERL-Michigan','Waves-GLERL-Ontario','Waves-GLERL-Superior'],['Waves-GLERL-Erie'],['Wave height (m)'],false,false,false,'','']
    ,['Winds (NAM)',['Winds'],['Winds'],false,false,false,false,'','']
  ]
";

  // ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical','conditionsReport','liteLegendLabel','liteLegendImage']
  $weatherMapsStoreDataJS = "[
     ['RADAR']
    ,['Base reflectivity',['Base reflectivity'],['Base reflectivity'],false,false,false,false,'','']
    ,['Satellite']
    ,['Chlorophyll concentration',['Chlorophyll-LakeMichigan','Chlorophyll-LakeErie','Chlorophyll-LakeHuron','Chlorophyll-LakeOntario','Chlorophyll-LakeSuperior'],['Chlorophyll-LakeMichigan','Chlorophyll-LakeErie'],['Chlorophyll concentration<br>(ug/L)','Chlorophyll concentration<br>(ug/L)'],false,[{source : ".$chl['legend'].",target : ['Lake Huron','Lake Michigan','Lake Superior']},{source : ".$chl['legend'].",target : ['Lake Erie','Lake Ontario','']}],false,'','']
    ,['Colored dissolved organic matter',['ColoredDissolvedOrganicMatter-LakeErie','ColoredDissolvedOrganicMatter-LakeHuron','ColoredDissolvedOrganicMatter-LakeMichigan','ColoredDissolvedOrganicMatter-LakeOntario','ColoredDissolvedOrganicMatter-LakeSuperior'],['ColoredDissolvedOrganicMatter-LakeMichigan'],['Colored dissolved organic matter<br>(absorption/m @ 443nm)<br><b>Satellite data generally unavailable from early November to late March due to cloud cover.</b>'],false,".getColoredDissolvedOrganicMatterTime().",false,'','']
    ,['Dissolved organic carbon',['DissolvedOrganicCarbon-LakeErie','DissolvedOrganicCarbon-LakeHuron','DissolvedOrganicCarbon-LakeMichigan','DissolvedOrganicCarbon-LakeOntario','DissolvedOrganicCarbon-LakeSuperior'],['DissolvedOrganicCarbon-LakeMichigan'],['Dissolved organic carbon<br>(mgC/L)<br><b>Satellite data generally unavailable from early November to late March due to cloud cover.</b>'],false,".getDissolvedOrganicCarbonTime().",false,'','']
    ,['Natural color',['NaturalColor-LakeMichigan','NaturalColor-LakeErie','NaturalColor-LakeHuron','NaturalColor-LakeOntario','NaturalColor-LakeSuperior'],['NaturalColor-LakeMichigan'],['Natural color<br><b>Satellite data generally unavailable from early November to late March due to cloud cover.</b>'],false,".getNaturalColorTime().",false,'','']
    ,['Suspended minerals',['SuspendedMinerals-LakeErie','SuspendedMinerals-LakeHuron','SuspendedMinerals-LakeMichigan','SuspendedMinerals-LakeOntario','SuspendedMinerals-LakeSuperior'],['SuspendedMinerals-LakeMichigan'],['Suspended minerals<br>(mg/L)<br><b>Satellite data generally unavailable from early November to late March due to cloud cover.</b>'],false,".getSuspendedMineralsTime().",false,'','']
    ,['Water surface temperature',['WaterSurfaceTemperature-LakeMichigan','WaterSurfaceTemperature-LakeErie','WaterSurfaceTemperature-LakeHuron','WaterSurfaceTemperature-LakeOntario','WaterSurfaceTemperature-LakeSuperior'],['WaterSurfaceTemperature-LakeMichigan'],['Water surface<br>temperature (deg F)<br><b>Satellite data generally unavailable from early November to late March due to cloud cover.</b>'],true,".getWaterSurfaceTemperatureTime().",false,'','']
  ]
";

  // ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical']
  $byCatchMapsStoreDataJS = "[
  ]
";

/*
  make buffer
  ogr2ogr -f "GeoJSON" buffer.json PG:"host=db0 user=postgres dbname=hm" -sql "select ST_Collect(ST_Buffer(ST_Simplify(the_geom,0.4),0.75)) as the_geom from lakes where name in ('Lake Superior','Lake Michigan','Lake Huron','Lake Erie','Lake Ontario')"

  for great lakes
  ogr2ogr -f "GeoJSON" greatLakes.json PG:"host=db0 user=postgres dbname=hm" -sql "select case when name = 'L. St. Clair' then 'Lake Saint Clair' else name end as name,ST_Buffer(ST_Simplify(the_geom,0.075),0.09) as the_geom from lakes where name in ('Lake Superior','Lake Michigan','Lake Huron','Lake Erie','Lake Ontario','L. St. Clair') order by case when name = 'L. St. Clair' then 'Lake Saint Clair' else name end"
*/

  function getWaterSurfaceTemperatureTime() {
    $lakes = array('Lake Erie','Lake Huron','Lake Michigan','Lake Ontario','Lake Superior');
    $a = array();
    for ($i = 0; $i < count($lakes); $i++) {
      $l = $lakes[$i];
      $xml = @simplexml_load_file('xml/glosWaterSurfaceTemperature'.str_replace(' ','',$l).'.getcaps.xml');
      foreach ($xml->{'Capability'}[0]->{'Layer'}[0]->{'Layer'} as $l0) {
        foreach ($l0->{'Layer'} as $l1) {
          if (sprintf("%s",$l1->{'Name'}) == 'sst') {
            array_push($a,'["'.$l.'",new Date('.strtotime(sprintf("%s",$l1->{'Dimension'}[0]->attributes()->{'default'})).' * 1000)]');
          }
        }
      }
    }
    if (count($a) > 0) {
      return '['.implode(',',$a).']';
    }
    else {
      return false;
    }
  }

  function getSuspendedMineralsTime() {
    $lakes = array('Lake Erie','Lake Huron','Lake Michigan','Lake Ontario','Lake Superior');
    $a = array();
    for ($i = 0; $i < count($lakes); $i++) {
      $l = $lakes[$i];
      $xml = @simplexml_load_file('xml/glosSuspendedMinerals'.str_replace(' ','',$l).'.getcaps.xml');
      foreach ($xml->{'Capability'}[0]->{'Layer'}[0]->{'Layer'} as $l0) {
        foreach ($l0->{'Layer'} as $l1) {
          if (sprintf("%s",$l1->{'Name'}) == 'sm') {
            array_push($a,'["'.$l.'",new Date('.strtotime(sprintf("%s",$l1->{'Dimension'}[0]->attributes()->{'default'})).' * 1000)]');
          }
        }
      }
    }
    if (count($a) > 0) {
      return '['.implode(',',$a).']';
    }
    else {
      return false;
    }
  }

  function getDissolvedOrganicCarbonTime() {
    $lakes = array('Lake Erie','Lake Huron','Lake Michigan','Lake Ontario','Lake Superior');
    $a = array();
    for ($i = 0; $i < count($lakes); $i++) {
      $l = $lakes[$i];
      $xml = @simplexml_load_file('xml/glosDissolvedOrganicCarbon'.str_replace(' ','',$l).'.getcaps.xml');
      foreach ($xml->{'Capability'}[0]->{'Layer'}[0]->{'Layer'} as $l0) {
        foreach ($l0->{'Layer'} as $l1) {
          if (sprintf("%s",$l1->{'Name'}) == 'doc') {
            array_push($a,'["'.$l.'",new Date('.strtotime(sprintf("%s",$l1->{'Dimension'}[0]->attributes()->{'default'})).' * 1000)]');
          }
        }
      }
    }
    if (count($a) > 0) {
      return '['.implode(',',$a).']';
    }
    else {
      return false;
    }
  }

  function getColoredDissolvedOrganicMatterTime() {
    $lakes = array('Lake Erie','Lake Huron','Lake Michigan','Lake Ontario','Lake Superior');
    $a = array();
    for ($i = 0; $i < count($lakes); $i++) {
      $l = $lakes[$i];
      $xml = @simplexml_load_file('xml/glosColoredDissolvedOrganicMatter'.str_replace(' ','',$l).'.getcaps.xml');
      foreach ($xml->{'Capability'}[0]->{'Layer'}[0]->{'Layer'} as $l0) {
        foreach ($l0->{'Layer'} as $l1) {
          if (sprintf("%s",$l1->{'Name'}) == 'cdom') {
            array_push($a,'["'.$l.'",new Date('.strtotime(sprintf("%s",$l1->{'Dimension'}[0]->attributes()->{'default'})).' * 1000)]');
          }
        }
      }
    }
    if (count($a) > 0) {
      return '['.implode(',',$a).']';
    }
    else {
      return false;
    }
  }

  function getChlorophyllTime() {
    $lyr2niceName = array(
       'CHLLOW_Michigan' => 'Lake Michigan'
      ,'CHL_Erie'        => 'Lake Erie'
      ,'CHLLOW_Huron'    => 'Lake Huron'
      ,'CHL_Ontario'     => 'Lake Ontario'
      ,'CHLLOW_Superior' => 'Lake Superior'
    );
    $regex = implode('|',array_keys($lyr2niceName));
    $times = array();
    $xml = @simplexml_load_file('xml/glosChlorophyll.getcaps.xml');
    foreach ($xml->{'Capability'}[0]->{'Layer'}[0]->{'Layer'} as $l0) {
      if (preg_match("/^($regex)_(.*)/",sprintf("%s",$l0->{'Name'}),$matches)) {
        if (!array_key_exists($lyr2niceName[$matches[1]],$times) || $times[$lyr2niceName[$matches[1]]] < $matches[2]) {
          $times[$lyr2niceName[$matches[1]]] = $matches[2];
        }
      }
    }

    $a = array();
    $lut = array();
    foreach ($times as $k => $v) {
      $t = strtotime($v.' America/Detroit');
      array_push($a,'["'.$k.'",new Date('.$t.' * 1000)]');
      $lut[$k] = date("Ymd",$t);
    }

    if (count($a) > 0) {
      return array('legend' => '['.implode(',',$a).']','times' => $lut);
    }
    else {
      return array('legend' => false);
    }
  }

  function getNaturalColorTime() {
    $lakes = array('Lake Erie','Lake Huron','Lake Michigan','Lake Ontario','Lake Superior');
    $a = array();
    for ($i = 0; $i < count($lakes); $i++) {
      $l = $lakes[$i];
      $xml = @simplexml_load_file('xml/glosNaturalColor'.str_replace(' ','',$l).'.getcaps.xml');
      foreach ($xml->{'Capability'}[0]->{'Layer'}[0]->{'Layer'} as $l0) {
        if (sprintf("%s",$l0->{'Name'}) == 'Band1') { 
          foreach ($l0->{'Extent'} as $e) {
            if (sprintf("%s",$e->attributes()->{'name'}) == 'time') {
              // for now, pull apart the times string and assume that the last one is the default
              $times = preg_split("/,|\//",sprintf("%s",$e));
              array_push($a,'["'.$l.'",new Date('.strtotime($times[count($times)-1]).' * 1000)]');
            }
          }
        }
      }
    }
    if (count($a) > 0) {
      return '['.implode(',',$a).']';
    }
    else {
      return false;
    }
  }

  function getGetCapsInfo($name) {
    $info = array();
    $xml = @simplexml_load_file('xml/'.$name.'.getcaps.xml');
    foreach ($xml->{'Capability'}[0]->{'Layer'}[0]->{'Layer'} as $l0) {
      $info[sprintf("%s",$l0->{'Name'})] = array(
        'bbox' => implode(',',array(
           sprintf("%s",$l0->{'LatLonBoundingBox'}->attributes()->{'minx'})
          ,sprintf("%s",$l0->{'LatLonBoundingBox'}->attributes()->{'miny'})
          ,sprintf("%s",$l0->{'LatLonBoundingBox'}->attributes()->{'maxx'})
          ,sprintf("%s",$l0->{'LatLonBoundingBox'}->attributes()->{'maxy'})
        ))
      );
    }
    return $info;
  }
?>
