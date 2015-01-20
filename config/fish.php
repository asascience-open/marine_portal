<?php
  $title     = 'MyMARACOOS Fishing';
  $googleId  = 'UA-32877184-1';

  $mapCenter = '-73,38.5';
  $mapZoom   = 6;
  $basemap   = 'ESRI Ocean';
  $mode      = 'none';

  $viewer = 'lite';

  $search        = 'off';
  $tbar          = 'on';
  $byCatch       = 'on';
  $chat          = 'on';
  $bathyContours = 'on';

  $weatherTab = 'Remote';

  $dataBbox = '-77,35,-69,42';
  $buffer   = "xml/buffer.$dataBbox.json";

  $bannerHeight    = 64;
  $bannerHtml      = <<< EOHTML
<div id="head"><a href="http://maracoos.org"><img src="img/blank.png" width="300" height=65" alt="MARACOOS" title="Go to the MARACOOS home page"/></a></div>
<div id="sessionControls">
  <table><tr>
    <td><a class="headerlinks" href="javascript:printMap()">Print</a></td>
    <td><a class="headerlinks" href="javascript:linkMap()">Bookmark</a></td>
    <td><a class="headerlinks" href="javascript:startChat()">Comments</a></td>
    <td><a class="headerlinks" href="javascript:showSplash()">About</a></td>
    <td><a class="headerlinks" href="javascript:goSession()">Login</a></td>
  </tr></table>
</div>
EOHTML;
  $bannerHtml = str_replace("\n",' ',$bannerHtml);

  $southPanelHeight    = 0;
  $southPanelHtml      = <<< EOHTML
EOHTML;

  $catalogQueryXML = json_encode('');

  $obsLegendsPath = 'img/fish/';
  $obsCptRanges   = array(
     'winds'      => '0,30'
    ,'waves'      => '0,10'
    ,'watertemp'  => '40,90'
    ,'waterlevel' => '0,10'
  );

  $minZoom = array(
     'winds'      => 8
    ,'waves'      => 6
    ,'waterTemp'  => 8
    ,'waterLevel' => 8
    ,'streamflow' => 10
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

  $defaultObs = 'Waves';
  $defaultFC  = 'on';
  $defaultWWA = 'off';

  $availableObs = array('Winds','Waves','WaterTemp','WaterLevel');

  $extraInitJS = <<< EOJS
    // don't want byCatch on by default
    if ('$byCatch' == 'on' && !startupbyCatchLayer) {
      Ext.getCmp('byCatchTabPanel').setActiveTab(1);
    }

    if (!cp.get('hideSplashOnStartupCheckbox')) {
      showSplash();
    }
EOJS;

  $fcSliderIncrement = 6;

  $gfi = array(
    array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://64.72.74.123/geoserver/maracoos/wms?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=application/vnd.ogc.gml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&"
          ,'maracoos:byCatch.riverHerring.bottomTrawl.latest'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'maracoos:byCatch.riverHerring.bottomTrawl.latest'
        );
      }
      ,'fmt' => 'gml'
      ,'vars' => array(
        'val' => array(
           'name' => 'Bycatch : River herring bottom trawl'
          ,'fmt'  => "%s"
          ,'f'    => function($val,$a,$t) {
            if ($val >= 5) {
              return 'high';
            }
            else if ($val >= 1.50001) {
              return 'medium';
            }
            else if ($val >= 0.001) {
              return 'low';
            }
            else {
              return 'none';
            }
          }
        )
      )
      ,'map' => 'Bottom trawl Rhode Island'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://64.72.74.123/geoserver/maracoos/wms?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=application/vnd.ogc.gml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&"
          ,'maracoos:byCatch.butterfish.latest'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'maracoos:byCatch.butterfish.latest'
        );
      }
      ,'fmt' => 'gml'
      ,'vars' => array(
        'val' => array(
           'name' => 'Bycatch : Butterfish bottom trawl'
          ,'fmt'  => "%s"
          ,'f'    => function($val,$a,$t) {
            if ($val == 'Negative Report') {
              return 'negative report';
            }
            else if ($val == 'Low') {
              return 'low';
            }
            else if ($val == 'Mild') {
              return 'moderate';
            }
            else if ($val == 'High') {
              return 'high';
            }
            else if ($val == 'Urgent') {
              return 'urgent';
            }
            else {
              return 'none';
            }
          }
        )
      )
      ,'map' => 'Bottom trawl Northeast/MA'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://64.72.74.123/geoserver/maracoos/wms?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=application/vnd.ogc.gml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&"
          ,'maracoos:byCatch.riverHerring.midWaterTrawl.capecod.latest'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'maracoos:byCatch.riverHerring.midWaterTrawl.capecod.latest'
        );
      }
      ,'fmt' => 'gml'
      ,'vars' => array(
        'val' => array(
           'name' => 'Bycatch : River herring mid-water trawl Cape Cod'
          ,'fmt'  => "%s"
          ,'f'    => function($val,$a,$t) {
            switch ($val) {
              case '3' :
                return 'high';
                break;
              case '2' :
                return 'medium';
                break;
              case '1' :
                return 'low';
                break;
              default :
                return 'none';
            }
          }
        )
      )
      ,'map' => 'Mid-water trawl Cape Cod'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://64.72.74.123/geoserver/maracoos/wms?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=application/vnd.ogc.gml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&"
          ,'maracoos:byCatch.riverHerring.midWaterTrawl.area2.latest'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'maracoos:byCatch.riverHerring.midWaterTrawl.area2.latest'
        );
      }
      ,'fmt' => 'gml'
      ,'vars' => array(
        'val' => array(
           'name' => 'Bycatch : River herring mid-water trawl Area 2'
          ,'fmt'  => "%s"
          ,'f'    => function($val,$a,$t) {
            switch ($val) {
              case '3' :
                return 'high';
                break;
              case '2' :
                return 'medium';
                break;
              case '1' :
                return 'low';
                break;
              default :
                return 'none';
            }
          }
        )
      )
      ,'map' => 'Mid-water trawl Area 2'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://64.72.74.123/geoserver/maracoos/wms?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=application/vnd.ogc.gml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&"
          ,'maracoos:byCatch.scallopYellowtail.area1.latest'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'maracoos:byCatch.scallopYellowtail.area1.latest'
        );
      }
      ,'fmt' => 'gml'
      ,'vars' => array(
        'val' => array(
           'name' => 'Bycatch : Scallop/yellowtail closed area 1'
          ,'fmt'  => "%s"
          ,'f'    => function($val,$a,$t) {
            switch ($val) {
              case '3' :
                return 'high';
                break;
              case '2' :
                return 'medium';
                break;
              case '1' :
                return 'low';
                break;
              default :
                return 'none';
            }
          }
        )
      )
      ,'map' => 'Closed area 1 Georges Bank'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://64.72.74.123/geoserver/maracoos/wms?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=application/vnd.ogc.gml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&"
          ,'maracoos:byCatch.scallopYellowtail.area2.latest'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'maracoos:byCatch.scallopYellowtail.area2.latest'
        );
      }
      ,'fmt' => 'gml'
      ,'vars' => array(
        'val' => array(
           'name' => 'Bycatch : Scallop/yellowtail closed area 2'
          ,'fmt'  => "%s"
          ,'f'    => function($val,$a,$t) {
            switch ($val) {
              case '3' :
                return 'high';
                break;
              case '2' :
                return 'medium';
                break;
              case '1' :
                return 'low';
                break;
              default :
                return 'none';
            }
          }
        )
      )
      ,'map' => 'Closed area 2 Georges Bank'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://64.72.74.123/geoserver/maracoos/wms?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=application/vnd.ogc.gml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&"
          ,'maracoos:byCatch.scallopYellowtail.nantucket.latest'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'maracoos:byCatch.scallopYellowtail.nantucket.latest'
        );
      }
      ,'fmt' => 'gml'
      ,'vars' => array(
        'val' => array(
           'name' => 'Bycatch : Scallop/yellowtail Nantucket Lightship'
          ,'fmt'  => "%s"
          ,'f'    => function($val,$a,$t) {
            switch ($val) {
              case '3' :
                return 'high';
                break;
              case '2' :
                return 'medium';
                break;
              case '1' :
                return 'low';
                break;
              default :
                return 'none';
            }
          }
        )
      )
      ,'map' => 'Nantucket Lightship'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'HYCOM_GLOBAL_NAVY_CURRENTS'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'HYCOM_GLOBAL_NAVY_CURRENTS'
        );
      }
      ,'fmt' => 'xml'
      ,'vars' => array(
        'Water Velocity' => array(
           'name' => 'Current speed (kt)'
          ,'fmt'  => "%0.1f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Current direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (global)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'ESPRESSO_CURRENTS'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'ESPRESSO_CURRENTS'
        );
      }
      ,'fmt' => 'xml'
      ,'vars' => array(
        'Water Velocity' => array(
           'name' => 'Current speed (kt)'
          ,'fmt'  => "%0.1f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Current direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (regional)'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'NYHOPSCUR_currents'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'NYHOPSCUR_currents'
        );
      }
      ,'fmt' => 'xml'
      ,'vars' => array(
        'Water Velocity' => array(
           'name' => 'Current speed (kt)'
          ,'fmt'  => "%0.1f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction' => array(
           'name' => 'Current direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Currents (New York Harbor)'
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
      ,'fmt' => 'xml'
      ,'vars' => array(
        'Wind Velocity' => array(
           'name' => 'Wind speed (kt)'
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
      ,'map' => 'Winds'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'WW3_WAVE_HEIGHT'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'WW3_WAVE_HEIGHT'
        );
      }
      ,'fmt' => 'xml'
      ,'vars' => array( 
        'Height of Combined Wind, Waves and Swells' => array(
           'name' => 'Wave height (m)'
          ,'fmt'  => "%0.1f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Waves'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'WW3_WAVE_DIRECTION'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'WW3_WAVE_DIRECTION'
        );
      }
      ,'fmt' => 'xml'
      ,'vars' => array(
        'Wave Direction at Surface' => array(
           'name' => 'Wave direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return ($val - 180) < 0 ? $val + 180 : $val - 180;
          }
        )
      )
      ,'map' => 'Waves'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'HYCOM_GLOBAL_NAVY_SST'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'HYCOM_GLOBAL_NAVY_SST'
        );
      }
      ,'fmt' => 'xml'
      ,'vars' => array(
        'Water Temperature' => array(
           'name' => 'Surface water temperature (C)'
          ,'fmt'  => "%0.1f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Surface water temperature'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://wms.maracoos.org/wms/maracoos_espresso/?ELEVATION=0&LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/csv&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'temp'
          ,'pcolor_average_jet_5_20_node_False'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'temp'
        );
      }
      ,'fmt' => 'csv'
      ,'vars' => array(
        'temp' => array(
           'name' => 'Bottom water temperature (C)'
          ,'fmt'  => "%0.1f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Bottom water temperature'
    )
  );

  $byCatchInfo = getByCatchInfo();

  // ['panel','type','id','getMapUrl','getMapLayers','styles','format','timeParam','opacity','visibility','singleTile','moreInfo','bbox','legend']
  $mapLayersStoreDataJS = "[
    [
       'forecasts'
      ,'wms'
      ,'Surface water temperature'      
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'HYCOM_GLOBAL_NAVY_SST'       
      ,'WATER_TEMP-0-35'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'Sea surface temperature is from the HYbrid Coordinate Ocean Model (HYCOM), a generalized coordinate ocean model. It is isopycnal in the open stratified ocean but reverts to a terrain-following coordinate in shallow coastal regions. The global system deployed by the US NAVY employs atmospheric forcing form the NAVy Global Environmental Model (NAVGEM). Refer to hycom.org for more information on the general model.'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Bottom water temperature'       
      ,'http://wms.maracoos.org/wms/maracoos_espresso/?ELEVATION=0&'
      ,'temp'           
      ,'pcolor_average_jet_5_20_node_False'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'The Regional Ocean Modeling System (ROMS) ESPreSSO (Experimental System for Predicting Shelf and Slope Optics) model covers the Mid-Atlantic Bight from Cape Cod southward to Cape Hatteras, from the coast to beyond the shelf break and shelf/slope front. The prototype system is a 5-km horizontal, 36-level ROMS model with Incremental Strong Constraint 4DVAR assimilation of AVHRR and daily composite microwave+IR SST (REMSS), surface currents from HF-radar, along track altimeter SSH anomalies from Jason-2, AltiKa and CryoSat (via RADS) and in situ T/S from available gliders and GTS observations.'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Bottom water temperature contours'
      ,'http://wms.maracoos.org/wms/maracoos_espresso/?ELEVATION=0&'
      ,'temp'
      ,'contours_average_gray_5_20_node_False'
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
      ,'Waves'                          
      ,'http://coastmap.com/ecop/wms.aspx'                             
      ,'WW3_WAVE_HEIGHT'
      ,''
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'Wave Watch III (WW3) is a third generation wave model developed at NCEP. WW3 forecasts are produced every six hours at 00, 06, 12 and 18 UTC. The WW3 graphics are based model fields of 1.00 x 1.250 to 50 x 50 and are available at six hour increments out to 87 hours. WW3 solves the spectral action density balance equation for wave number-direction spectra. Assumptions for the model equations imply that the model can generally be applied on spatial scales (grid increments) larger than 1 to 10 km, and outside the surf zone.'
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'Chlorophyll concentration'
      ,'http://wms.maracoos.org/wms/MODIS_Eight_Agg/?ELEVATION=0&'
      ,'chl_oc3'
      ,'pcolor_average_jet_0.01_20_node_Log'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'<b>High values (greens to reds) typically mean the water is turbid and cloudy, while low values (blues) mean the water is very clear.</b><br>Chlorophyll concentrations are estimated using the ocean color information coming from the MODIS-Aqua satellite system. The data is processed by researchers at the University of Delaware and re-gridded to Mercator lat/long projection. This layer is considered an experimental research product. The layer is a <b>3-day data composite</b>, which reduces gaps in spatial coverage due to cloud cover, although gaps may persist during cloudy seasons. The MODIS-Aqua sensor is well past its mission design lifetime, and the sensor is beginning to degrade, often causing noticeable <b>parallel stripes</b> in the data. Periodic re-processing of the MODIS data stream will occur in the near future, but the long term solution for this product is the transition to the VIIRS sensor. NOAA\'s provisional plans are to have the VIIRS sensor operational sometime in late 2013.'
      ,false
      ,{slope : 1,offset : 0,format : '%d',log : true}
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents (regional)'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'ESPRESSO_CURRENTS'
      ,'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'The Regional Ocean Modeling System (ROMS) ESPreSSO (Experimental System for Predicting Shelf and Slope Optics) model covers the Mid-Atlantic Bight from the center of Cape Cod southward to Cape Hatters, from the coast to beyond the shelf break and shelf/slope front. The prototype system is a 5-km horizontal, 36-level ROMS model with Incremental Strong Constraint 4DVAR assimilation of AVHRR and daily composite SST (remss) and along track altimeter SSH anomalies (RADS).'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents (New York Harbor)'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'NYHOPSCUR_currents'
      ,'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'The Stevens New York Harbor Observing and Prediction System (NYHOPS) permits an assessment of ocean, weather, and environmental conditions throughout the New York Harbor, Hudson-Raritan Estuary, Long Island Sound and the waters eastward to the continental shelf break of the Middle Atlantic Bight in one contiguous fashion. It provides real-time observations and 48-hour predictions of ocean conditions (water level, currents, waves, salinity, and temperature) and weather conditions.'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents (global)'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'HYCOM_GLOBAL_NAVY_CURRENTS'
      ,'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'Sea surface temperature is from the HYbrid Coordinate Ocean Model (HYCOM), a generalized coordinate ocean model. HYCOM is maintained by a multi-institutional consortium sponsored by the National Ocean Partnership Program (NOPP). The currents are assimilated from the Navy Coupled Ocean Data Assimilation (NCODA) System. Current profile data is collected from various sources such as XBTs, CTDs, PALACE floats, fixed buoys, and drifting buoy altimeters.'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Winds'                          
      ,'http://coastmap.com/ecop/wms.aspx'                             
      ,'NAM_WINDS'      
      ,'WINDS_VERY_SPARSE_GRADIENT-False-1-0-45-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'The NAM model is a regional mesoscale data assimilation and forecast model system based on the WRF common modeling infrastructure, currently running at 12 km resolution and 60 layers. NAM forecasts are produced every six hours at 00, 06, 12 and 18 UTC. The NAM graphics are available at three hour increments out to 84 hours. The NAM has non-hydrostatic dynamics and a full suite of physical parameterizations and a land surface model.'
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'Satellite water temperature'
      ,'http://wms.maracoos.org/wms/maracoos_SST_Seven_Agg/?ELEVATION=0&'
      ,'mcsst'
      ,'pcolor_average_jet_0_27_node_False'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'Sea surface temperature data displayed in this layer comes from multiple NOAA satellites (including the AVHRR). The layer is a <b>7-day data composite</b>. Cloud contamination is provisionally removed for plotting purposes only. Occasionally, cool water may be discarded as cloud contamination. The data is processed by researchers at the University of Delaware and re-gridded to Mercator lat/long projection. This layer is considered an experimental research product.'
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'Ocean fronts coverage'
      ,'http://wms.maracoos.org/wms/MODIS_Eight_Agg/'
      ,'M_WK'
      ,'pcolor_average_Accent-r_1_1_grid_Log'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,false
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wms'
      ,'Ocean fronts'
      ,'http://wms.maracoos.org/wms/MODIS_Eight_Agg/'
      ,'M_WK_G'
      ,'pcolor_average_jet_0.1_3.2_grid_Log'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'<b>High values (greens to reds) indicate a strong surface front in either temperature or ocean color, low values (blues) indicate a presence of a weak front in temperature or ocean color.</b><br>Ocean frontal boundaries in this layer are calculated using a gradient strength index, which estimates the differences between water types or masses. This index incorporates both temperature and ocean color from the MODIS-Aqua satellite system to detect both hydrographic and biological fronts. Data gaps are due to cloud cover <b>(no data indicted by clear cells or no coloration). Grey coloration indicates very low or 0 gradient strength.</b> The layer is available as a <b>1-day, 3-day, or 8-day data composite</b>. Each composite option successively reduces gaps in spatial coverage due to <b>cloud cover and daily satellite pass/swath width</b>. The data is processed by researchers at the University of Delaware and re-gridded to Mercator lat/long projection. This layer is considered an experimental research product. The MODIS-Aqua sensor is well past its mission design lifetime, and the sensor is beginning to degrade, often causing noticeable <b>parallel stripes</b> in the data. This may produce <b>\'pseudo\'</b> fronts in the data stream where no front really exists. Sets of long, parallel fronts may be a result of sensor degradation. Periodic re-processing of the MODIS data stream will occur in the near future, but the long term solution for this product is the transition to the VIIRS sensor. NOAA\'s provisional plans are to have the VIIRS sensor operational sometime in late 2013.'
      ,false
      ,false
    ]
    ,[
       'weather'
      ,'wunderground'
      ,'Cloud imagery'
      ,'http://api.wunderground.com/api/cd3aefa3b6d50f6a/satellite/image.png?GetMetadata=1&smooth=1&gtt=107&key=sat_ir4'
      ,''
      ,''
      ,''
      ,false
      ,1
      ,false
      ,true
      ,'Infrared satellite technology works by sensing the temperature of infrared radiation being emitted into space from the earth and its atmosphere. Basically, all objects (including water, land, and clouds), radiate infrared light. However, our eyes are not \'tuned\' to see this kind of light, so we don\'t notice it. Weather satellites not only sense this infrared light, but they can also sense the temperature of the infrared emissions.  For more information, read Weather Underground\'s layer details <a target=_blank href=\'http://www.wunderground.com/about/satellite.asp\'>here</a>.'
      ,false
      ,{slope : -999,offset : -999,format : '',image : 'http://icons-ak.wxug.com/graphics/wu2/key_gSat_Wide.gif'}
    ]
    ,[
       'weather'
      ,'wunderground'
      ,'Weather RADAR'
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
    ,[
       'showByCatch'
      ,'wms'
      ,'River herring bottom trawl'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.riverHerring.bottomTrawl.latest'
      ,'byCatch.poly'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,'Cumulative river herring bottom trawl bycatch advisory information for the Rhode Island area. River herring and shad catch amounts are generated from portside and at sea observations of vessels targeting Atlantic herring or mackerel and offloading in Rhode Island. Catch information is trip level, one trip may occur in several cells. Grid lines follow 5\' longitude and 2.5\' latitude lines. Questions or comments contact Dave at nbethoney@umassd.edu or 1-508-910-6386.'
      ,'".$byCatchInfo['byCatch.riverHerring.bottomTrawl.latest']['bbox']."'
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'Butterfish bottom trawl grid'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.butterfish.grid.latest'
      ,'grayLine'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'Butterfish bottom trawl'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.butterfish.latest'
      ,'byCatch.butterfish.poly'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,'Cornell Cooperative Extension has received funding from NFWF to initiate a fleet communication system to abate butterfish bycatch in the longfin squid bottom trawl fishery. Since the 2 species exhibit similar migratory patterns and habitat preferences, the squid fishery observes considerable amounts of butterfish bycatch. With butterfish bycatch cap limitations in effect, the longfin squid fishery is threatened by early closures and cancellations. Due to the commercial significance of this fishery, the need for a bycatch reduction method is compulsory. This is an alternative approach to bycatch reduction devices and mesh size modification for reducing bycatch. The fleet communication system collects and reports real-time observations of butterfish through Boatracs and identifies \\'hot spots\\' from vessels engaged in the squid fishery. This enables the fleet to avoid \\'hot spots\\' and reduce fleet-wide capture of butterfish. \\'Hot spots\\' (areas with high concentrations of butterfish within a tow) are identified by cell number on a chart with a 10 minute square grid. For more info call Kristin Gerbino at (631) 727-7850 x315 or go to <a href=\\'http://www.squidtrawlnetwork.com\\' target=_blank>www.squidtrawlnetwork.com</a>.'
      ,'-74.5000071843465,38.4999941190082,-71.5000071843465,41.1666607856749' // select st_astext(st_envelope(st_buffer(st_collect(the_geom),1))) from by_catch_butterfish_latest where val is not null;
//      ,'".$byCatchInfo['byCatch.butterfish.latest']['bbox']."'
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'River herring bottom trawl grid'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.riverHerring.bottomTrawl.grid.latest'
      ,'grayLine'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'River herring mid-water trawl Cape Cod'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.riverHerring.midWaterTrawl.capecod.latest'
      ,'byCatch.poly'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,'Cumulative river herring mid-water trawl bycatch advisory information for the Atlantic herring management Cape Cod area. Information on this website is cumulative but fleet advisories only include information less than one week old. Cell classifications are determined by most recent catch. Grid lines follow 10\' longitude and 5\' latitude lines. A single tow may occur in several cells and vessels generally tow in pairs. Questions or comments contact Dave at nbethoney@umassd.edu or 1-508-910-6386.'
      ,'".$byCatchInfo['byCatch.riverHerring.midWaterTrawl.capecod.latest']['bbox']."'
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'River herring mid-water trawl Cape Cod grid'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.riverHerring.midWaterTrawl.capecod.grid.latest'
      ,'grayLine'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'River herring mid-water trawl Area 2'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.riverHerring.midWaterTrawl.area2.latest'
      ,'byCatch.poly'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,'Cumulative river herring mid-water trawl bycatch advisory information for Area 2. Information on this website is cumulative but fleet advisories only include information less than one week old. Cell classifications are determined by most recent catch. Grid lines follow 10\' longitude and 5\' latitude lines. A single tow may occur in several cells and vessels generally tow in pairs. Questions or comments contact Dave at nbethoney@umassd.edu or 1-508-910-6386.'
      ,'".$byCatchInfo['byCatch.riverHerring.midWaterTrawl.area2.latest']['bbox']."'
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'River herring mid-water trawl Area 2 grid'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.riverHerring.midWaterTrawl.area2.grid.latest'
      ,'grayLine'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'Scallop/yellowtail closed area 1 grid'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.scallopYellowtail.area1.grid.latest'
      ,'grayLine'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'Scallop/yellowtail closed area 1'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.scallopYellowtail.area1.latest'
      ,'byCatch.point'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,'Closed Area 1 yellowtail bycatch advisory information, based on data received between 8/1/12 and 10/15/12.'
      ,'".$byCatchInfo['byCatch.scallopYellowtail.area1.latest']['bbox']."'
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'Scallop/yellowtail closed area 2 grid'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.scallopYellowtail.area2.grid.latest'
      ,'grayLine'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'Scallop/yellowtail closed area 2'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.scallopYellowtail.area2.latest'
      ,'byCatch.point'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,'Closed Area 2 yellowtail bycatch advisory information, based on data received between 8/1/12 and 10/15/12.'
      ,'".$byCatchInfo['byCatch.scallopYellowtail.area2.latest']['bbox']."'
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'Scallop/yellowtail Nantucket Lightship grid'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.scallopYellowtail.nantucket.grid.latest'
      ,'grayLine'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,''
      ,false
      ,false
    ]
    ,[
       'showByCatch'
      ,'wms'
      ,'Scallop/yellowtail Nantucket Lightship'
      ,'http://64.72.74.123/geoserver/maracoos/wms'
      ,'maracoos:byCatch.scallopYellowtail.nantucket.latest'
      ,'byCatch.point'
      ,'image/png'
      ,false
      ,1.0
      ,false
      ,true
      ,'Nantucket Lightship Area yellowtail bycatch advisory information, based on data received between 8/1/12 and 10/15/12.'
      ,'".$byCatchInfo['byCatch.scallopYellowtail.nantucket.latest']['bbox']."'
      ,false
    ]
  ]
";

  // ['id','wmsLayers','wmsLegends','showLegendTitle','legendLiteTitle','visibility','historical','conditionsReport','liteLegendLabel','liteLegendImage']
  $forecastMapsStoreDataJS = "[
     ['Bottom water temperature'            ,['Bottom water temperature']       ,['Bottom water temperature']  ,false,false,false,true
       ,'Temperature<br>(deg F)','$obsLegendsPath/BottomWaterTemp.png']
    ,['Currents (global)'                   ,['Currents (global)']              ,['Currents (global)']         ,false,false,false,true
      ,'Currents<br>(knots)','$obsLegendsPath/CurrentSpeed.png']
    ,['Currents (regional)'                 ,['Currents (regional)']            ,['Currents (regional)']       ,false,false,false,false
      ,'Currents<br>(knots)','$obsLegendsPath/CurrentSpeed.png']
    ,['Currents (New York Harbor)'          ,['Currents (New York Harbor)']     ,['Currents (New York Harbor)'],false,false,false,false
      ,'Currents<br>(knots)','$obsLegendsPath/CurrentSpeed.png']
    ,['Surface water temperature'           ,['Surface water temperature']      ,['Surface water temperature'] ,false,false,false,true
      ,'Temperature<br>(deg F)','$obsLegendsPath/SurfaceWaterTemp.png']
    ,['Winds'                               ,['Winds']                          ,['Winds']                     ,false,true,false,true
      ,'Wind speed<br>(knots)','$obsLegendsPath/WindSpeed.png']
    ,['Waves'                               ,['Waves']                          ,['Waves']                     ,false,false,false,true
      ,'Wave height<br>(feet)','$obsLegendsPath/WaveHeight.png']
  ]
";

  // ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical','conditionsReport','liteLegendLabel','liteLegendLabel','liteLegendImage']
  $weatherMapsStoreDataJS = "[
     ['Satellite']
    ,['Chlorophyll concentration',['Chlorophyll concentration'],['Chlorophyll concentration'],['Chlorophyll concentration<br>(mg m^-3)'],true,false,false
      ,'Chl. conc.<br>(mg m^-3)','$obsLegendsPath/Chlorophyll.png']
    ,['Ocean fronts',['Ocean fronts coverage','Ocean fronts'],['Ocean fronts'],false,true,false,false
      ,'Ocean<br>fronts','$obsLegendsPath/OceanFronts.png']
    ,['Cloud imagery',['Cloud imagery'],['Cloud imagery'],['Cloud imagery'],false,false,false
      ,'Cloud<br>imagery','$obsLegendsPath/CloudImagery.gif']
    ,['Satellite water temperature',['Satellite water temperature'],['Satellite water temperature'],false,true,false,false
      ,'Temperature<br>(deg F)','$obsLegendsPath/SurfaceWaterTemp.png']
    ,['RADAR']
    ,['Weather RADAR',['Weather RADAR'],['Weather RADAR'],['Weather RADAR'],false,false,false
      ,'Weather<br>RADAR','$obsLegendsPath/WeatherRADAR.png']
    ,['Weather RADAR and cloud imagery',['Weather RADAR','Cloud imagery'],['Weather RADAR','Cloud imagery'],['Weather RADAR','Cloud imagery'],false,false,false
      ,'Weather<br>RADAR','$obsLegendsPath/WeatherRADAR.png']
  ]
";

  // ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical']
  $byCatchMapsStoreDataJS = "[
     ['Butterfish']
    ,[
       'Bottom trawl Northeast/MA'
      ,['Butterfish bottom trawl','Butterfish bottom trawl grid']
      ,['Butterfish bottom trawl']
      ,['Butterfish<br>Northeast/MA<br>Bottom trawl<br>Updated 9/19/13<br>Hotspots expire 9/21/13']
      ,true
      ,false
    ]
    ,['River herring']
    ,[
       'Bottom trawl Rhode Island'
      ,['River herring bottom trawl','River herring bottom trawl grid']
      ,['River herring bottom trawl']
      ,['River herring<br>Rhode Island<br>Bottom trawl<br>Updated 3/16/13']
      ,true
      ,false
    ]
    ,[
       'Mid-water trawl Area 2'
      ,['River herring mid-water trawl Area 2','River herring mid-water trawl Area 2 grid']
      ,['River herring mid-water trawl Area 2']
      ,['River herring<br>Area 2<br>Mid-water trawl<br>Updated 3/19/13']
      ,false
      ,false
    ]
    ,[
       'Mid-water trawl Cape Cod'
      ,['River herring mid-water trawl Cape Cod','River herring mid-water trawl Cape Cod grid']
      ,['River herring mid-water trawl Cape Cod']
      ,['River herring<br>Cape Cod<br>Mid-water trawl<br>Updated 2/11/13']
      ,false
      ,false
    ]
    ,['Scallop/yellowtail (historical)']
    ,[
       'Closed area 1 Georges Bank'
      ,['Scallop/yellowtail closed area 1 grid','Scallop/yellowtail closed area 1']
      ,['Scallop/yellowtail closed area 1']
      ,['Scallop/yellowtail<br>closed area 1<br>Georges Bank<br>2012 8/1 - 10/15']
      ,false
      ,false
    ]
    ,[
       'Closed area 2 Georges Bank'
      ,['Scallop/yellowtail closed area 2 grid','Scallop/yellowtail closed area 2']
      ,['Scallop/yellowtail closed area 2']
      ,['Scallop/yellowtail<br>closed area 2<br>Georges Bank<br>2012 8/1 - 10/15']
      ,false
      ,false
    ]
    ,[
       'Nantucket Lightship'
      ,['Scallop/yellowtail Nantucket Lightship grid','Scallop/yellowtail Nantucket Lightship']
      ,['Scallop/yellowtail Nantucket Lightship']
      ,['Scallop/yellowtail<br>Nantucket Lightship<br>2012 8/1 - 10/15']
      ,false
      ,false
    ]
  ]
";

  function getByCatchInfo() {
    $info = array();
    $xml = @simplexml_load_file('xml/fishByCatch.getcaps.xml');
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
