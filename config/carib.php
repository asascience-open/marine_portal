<?php
  $title     = 'Caribbean Assets Explorer';
  $googleId  = 'UA-37017862-1';

  $mapCenter = '-72.5,17.5';
  $mapZoom   = 5;
  $basemap   = 'ESRI Ocean';
  $mode      = 'observations';

  $search        = 'off';
  $tbar          = 'on';
  $byCatch       = 'off';
  $chat          = 'off';
  $bathyContours = 'off';

  $weatherTab = 'Remote';

  $dataBbox = '-86,6,-59,29';
  $buffer   = "xml/buffer.$dataBbox.json";

  $bannerHeight    = 60;
  $bannerHtml      = <<< EOHTML
<div id="head"><a href="http://iocaribe.ioc-unesco.org/"><img src="img/blank.png" width="200" height=60" alt="IOCARIBE-GOOS" title="Go to IOCARIBE home page"/></a></div>
EOHTML;

  $southPanelHeight    = 0;
  $southPanelHtml      = <<< EOHTML
EOHTML;

  $catalogQueryXML = json_encode('');

  $obsLegendsPath = 'img/carib/';
  $obsCptRanges   = array(
     'winds'      => '0,30'
    ,'waves'      => '0,10'
    ,'watertemp'  => '40,90'
    ,'waterlevel' => '0,10'
  );

  $minZoom = array(
     'winds'      => 8
    ,'waves'      => 5
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
EOJS;

  $fcSliderIncrement = 6;

  $gfi = array(
    array(
      'u' => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'ADCIRC_EAST_CURRENTS'
          ,''
          ,$srs,$bbox,$x,$y,$w,$h
          ,'ADCIRC_EAST_CURRENTS'
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
      ,'map' => 'Currents (ADCIRC)'
    )
    ,array(
      'u' => function($srs,$bbox,$x,$y,$w,$h) {
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
      ,'map' => 'Currents (HYCOM)'
    )
    ,array(
      'u' => function($srs,$bbox,$x,$y,$w,$h) {
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
      ,'map' => 'Winds & waves'
    )
    ,array(
      'u' => function($srs,$bbox,$x,$y,$w,$h) {
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
      ,'map' => 'Winds & waves'
    )
    ,array(
      'u' => function($srs,$bbox,$x,$y,$w,$h) {
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
            return $val;
          }
        )
      )
      ,'map' => 'Winds & waves'
    )
    ,array(
      'u'     => function($srs,$bbox,$x,$y,$w,$h) {
        return sprintf(
          "http://ec2-107-21-136-52.compute-1.amazonaws.com:8080/wms/NAVY_HYCOM/?ELEVATION=0&LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/csv&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'water_temp'
          ,'pcolor_average_jet_5_20_node_False'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'water_temp'
        );
      }
      ,'fmt' => 'csv'
      ,'vars' => array(
        'water_temp' => array(
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
          "http://tds.maracoos.org/ncWMS/wms?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
          ,'modis/chl_oc3'
          ,'boxfill/rainbow'
          ,$srs,$bbox,$x,$y,$w,$h
          ,'modis/chl_oc3'
        );
      }
      ,'fmt' => 'xml'
      ,'forceVar' => array(
         'name' => 'Chlorophyll concentration'
        ,'uom'  => 'mg m^-3'
      )
      ,'vars' => array(
        'Chlorophyll concentration' => array(
           'name' => 'Chlorophyll concentration (mg m^-3)'
          ,'fmt'  => "%0.2f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
      ,'map' => 'Chlorophyll concentration'
    )
  );

  // ['panel','type','id','getMapUrl','getMapLayers','styles','format','timeParam','opacity','visibility','singleTile','moreInfo','bbox','legend']
  $mapLayersStoreDataJS = "[
    [
       'forecasts'
      ,'wms'
      ,'Surface water temperature'
      ,'http://ec2-107-21-136-52.compute-1.amazonaws.com:8080/wms/NAVY_HYCOM/?ELEVATION=0&'
      ,'water_temp'
      ,'pcolor_average_jet_0_25_grid_False'
      ,'image/png'
      ,true
      ,1
      ,false
      ,true
      ,'Sea surface temperature is from the HYbrid Coordinate Ocean Model (HYCOM), a generalized coordinate ocean model. HYCOM is maintained by a multi-institutional consortium sponsored by the National Ocean Partnership Program (NOPP). Sea surface temperature model is assimilated from in-situ XBTs, ARGO floats, and moored buoys.'
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
      ,0.5
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
      ,'http://tds.maracoos.org/ncWMS/wms?GetMetadata=1&COLORSCALERANGE=0.01,20&'
      ,'modis-seven/chl_oc3'
      ,'boxfill/rainbow'
      ,'image/png'
      ,true
      ,1
      ,false
      ,false
      ,'Information currently unavailable.'
      ,false
      ,{slope : 1,offset : 0,format : '%d',log : true}
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
       'forecasts'
      ,'wms'
      ,'Currents (ADCIRC)'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'ADCIRC_EAST_CURRENTS'
      ,'CURRENTS_RAMP-Jet-False-3-True-0-2-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'The ADCIRC Tidal Databases are available from the ADCIRC Development Group. These data were calculated using the domain decomposition-based parallel version of the two-dimensional, depth integrated ADCIRC Coastal Circulation and Storm Surge Model. The databases include the M2, S2, N2, O1, K1, Q1, M4, M6 and STEADY tidal constituents. Phases are reported relative to UTC. The tidal constituents are nonlinearly generated and have not been verified, and therefore should be used with considerable caution.'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Currents (HYCOM)'
      ,'http://coastmap.com/ecop/wms.aspx'
      ,'HYCOM_GLOBAL_NAVY_CURRENTS'
      ,'CURRENTS_RAMP-Jet-False-1-True-0-2-High'
      ,'image/png'
      ,true
      ,1
      ,true
      ,true
      ,'Information currently unavialable.'
      ,false
      ,false
    ]
    ,[
       'forecasts'
      ,'wms'
      ,'Winds'                          
      ,'http://coastmap.com/ecop/wms.aspx'                             
      ,'NAM_WINDS'      
      ,'WINDS_VERY_SPARSE_GRADIENT-False-1-0-45-Low'
      ,'image/png'
      ,true
      ,0.5
      ,true
      ,true
      ,'The NAM model is a regional mesoscale data assimilation and forecast model system based on the WRF common modeling infrastructure, currently running at 12 km resolution and 60 layers. NAM forecasts are produced every six hours at 00, 06, 12 and 18 UTC. The NAM graphics are available at three hour increments out to 84 hours. The NAM has non-hydrostatic dynamics and a full suite of physical parameterizations and a land surface model.'
      ,false
      ,false
    ]
  ]
";

  // ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical']
  $forecastMapsStoreDataJS = "[
     ['Currents (ADCIRC)'              ,['Currents (ADCIRC)']              ,['Currents (ADCIRC)']        ,false,false,false]
    ,['Currents (HYCOM)'               ,['Currents (HYCOM)']               ,['Currents (HYCOM)']         ,false,false,false]
    ,['Surface water temperature'      ,['Surface water temperature']      ,['Surface water temperature'],false,false,false]
    ,['Winds & waves'                  ,['Waves','Winds']                  ,['Waves','Winds']            ,false,true,false]
  ]
";

  // ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical']
  $weatherMapsStoreDataJS = "[
     ['Satellite']
    ,['Chlorophyll concentration',['Chlorophyll concentration'],['Chlorophyll concentration'],['Chlorophyll concentration<br>(mg m^-3)'],true,false,false]
    ,['RADAR']
    ,['Weather RADAR',['Weather RADAR'],['Weather RADAR'],['Weather RADAR'],false,false,false]
    ,['Weather RADAR + cloud imagery',['Weather RADAR','Cloud imagery'],['Weather RADAR','Cloud imagery'],['Weather RADAR','Cloud imagery'],false,false,false]
  ]
";

  // ['id','wmsLayers','wmsLegends','showLegendTitle','visibility','historical']
  $byCatchMapsStoreDataJS = "[
  ]
";

?>
