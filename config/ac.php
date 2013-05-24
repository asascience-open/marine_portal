<?php
  $title     = "COASTMAP for America's Cup World Series";
  $googleId  = 'UA-32877859-1';

  $mapCenter = '-71.34671438158185,41.4800686513752';
  $mapZoom   = 13;
  $basemap   = 'Google Satellite';

  $dataBbox = '-71.58,41.297,-70.97,41.679';
  $buffer   = "xml/buffer.$dataBbox.json";

  $bannerHeight    = 66;
  $bannerHtml      = <<< EOHTML
<div id="headwrapper"> <table id="head"><tr> <td><a href="http://asascience.com"><img src="img/blank.png" width=160 height=60 title="Applied Science Associates"></a> </tr></table> </div>
EOHTML;
  $bannerStyle = <<< EOCSS
#headwrapper {
  background-image    : url("img/ac/banner.jpg");
  background-repeat   : no-repeat;
  background-position : left top;
  height              : 66px;
EOCSS;

  $southPanelHeight    = 0;
  $southPanelHtml      = <<< EOHTML
EOHTML;
  $southPanelBodyStyle = <<< EOCSS
EOCSS;

  $iconsPath    = getenv('icons');

  $minZoom = array(
     'winds'      => 7
    ,'waves'      => 5
    ,'waterTemp'  => 7
    ,'waterLevel' => 8
    ,'streamflow' => 10
  );

  $defaultObs = 'Winds';
  $defaultFC  = 'on';
  $defaultWWA = 'off';

  $availableObs = array('Winds','WaterTemp','WaterLevel');

  $obsOther         = 'off';
  $obsSearch        = 'off';
  $controlPanelTbar = 'off';

  $extraInitJS = <<< EOJS
    var pixel = map.getPixelFromLonLat(new OpenLayers.LonLat(-71.34671438158185,41.49).transform(proj4326,proj900913));
    mapClick(pixel);
    new Ext.ToolTip({
       title        : 'Point forecast'
      ,html         : 'The graph below represents a forecast for this location.  Click anywhere on the map to generate a new one.'
      ,closable     : true
      ,dismissDelay : 10000
    }).showAt([pixel.x,pixel.y]);

    var geojson = new OpenLayers.Format.GeoJSON();
    var f = geojson.read({
"type": "FeatureCollection",
"features": [
{ "type": "Feature", "properties": { "Id": 0 }, "geometry": { "type": "Polygon", "coordinates": [ [ [ -71.340184, 41.481761 ], [ -71.341280, 41.480155 ], [ -71.342748, 41.478221 ], [ -71.343297, 41.477610 ], [ -71.344152, 41.476267 ], [ -71.345128, 41.475596 ], [ -71.345867, 41.474672 ], [ -71.346471, 41.474070 ], [ -71.347814, 41.473399 ], [ -71.349096, 41.473216 ], [ -71.350316, 41.473216 ], [ -71.351537, 41.473399 ], [ -71.352514, 41.473826 ], [ -71.353429, 41.474497 ], [ -71.354039, 41.475413 ], [ -71.354284, 41.476267 ], [ -71.354406, 41.477122 ], [ -71.354284, 41.478037 ], [ -71.354039, 41.478709 ], [ -71.353612, 41.479380 ], [ -71.352941, 41.480235 ], [ -71.352208, 41.481089 ], [ -71.351550, 41.481551 ], [ -71.351051, 41.481551 ], [ -71.349954, 41.480853 ], [ -71.349356, 41.481651 ], [ -71.348241, 41.483103 ], [ -71.347570, 41.484202 ], [ -71.346776, 41.485056 ], [ -71.346227, 41.485972 ], [ -71.345678, 41.486582 ], [ -71.345128, 41.487254 ], [ -71.343873, 41.487932 ], [ -71.342321, 41.487681 ], [ -71.340856, 41.487071 ], [ -71.340184, 41.486155 ], [ -71.339757, 41.485301 ], [ -71.339513, 41.483958 ], [ -71.339452, 41.482859 ], [ -71.340184, 41.481761 ] ] ] } }

]
});
    f[0].geometry.transform(proj4326,proj900913);
    var l = new OpenLayers.Layer.Vector('course',{
      styleMap   : new OpenLayers.StyleMap({
        'default' : new OpenLayers.Style(OpenLayers.Util.applyDefaults({
           strokeColor : '#ff00ff'
          ,strokeWidth : 4
          ,fillOpacity : 0
        }))
      })
    });
    l.addFeatures(f);
    map.addLayer(l);

    map.events.register('addlayer',this,function(e) {
      map.setLayerIndex(map.getLayersByName('queryPt')[0],map.layers.length - 1);
      map.setLayerIndex(map.getLayersByName('course')[0],map.layers.length - 1);
    });
EOJS;

  $fcSliderIncrement = 1;

  $gfi = array(
/*
    array(
      'u'     => sprintf(
        "http://webserver.smast.umassd.edu:8000/wms/GOM3?ELEVATION=1&LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
        ,'salinity,temp'
        ,'facets_average_jet_0_32_node_False,facets_average_jet_0_32_node_False'
        ,$srs,$bbox,$x,$y,$w,$h
        ,'salinity,temp'
      )
      ,'fmt'  => 'csv'
      ,'vars' => array(
        'salinity' => array(
           'name' => 'Salinity (ppt)'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
        ,'temp' => array(
           'name' => 'Surface water temperature (C)'
          ,'fmt'  => "%0.1f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
    )
*/
    array(
      'u'     => sprintf(
        "http://webserver.smast.umassd.edu:8000/wms/GOM3?ELEVATION=1&LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
        ,'temp'
        ,'facets_average_jet_0_32_node_False'
        ,$srs,$bbox,$x,$y,$w,$h
        ,'salinity,temp'
      )
      ,'fmt'  => 'csv'
      ,'vars' => array(
        'temp' => array(
           'name' => 'Surface water temperature (C)'
          ,'fmt'  => "%0.1f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
    )
    ,array(
      'u'     => sprintf(
        "http://webserver.smast.umassd.edu:8000/wms/GOM3?ELEVATION=1&LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
        ,'u,v'
        ,'vectors_average_jet_0_1.5_cell_True,vectors_average_jet_0_1.5_cell_True'
        ,$srs,$bbox,$x,$y,$w,$h
        ,'u,v'
      )
      ,'fmt'  => 'csv'
      ,'vars' => array(
         'u' => array(
           'name'    => 'Water speed (m/s)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return sqrt(pow($a['u']['v'][$t],2) + pow($a['v']['v'][$t],2));
          }
        )
        ,'v' => array(
           'name' => 'Water direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            $v = 90 - rad2deg(atan2($a['v']['v'][$t],$a['u']['v'][$t]));
            $v += $v < 0 ? 360 : 0;
            return $v % 360;
          }
        )
      )
    )
    ,array(
      'u'     => sprintf(
        "http://webserver.smast.umassd.edu:8000/wms/NecofsWave?ELEVATION=1&LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
        ,'uwind_speed,vwind_speed'
        ,'barbs_average_jet_2_10_cell_False,barbs_average_jet_2_10_cell_False'
        ,$srs,$bbox,$x,$y,$w,$h
        ,'uwind_speed,vwind_speed'
      )
      ,'fmt'  => 'csv'
      ,'vars' => array(
        'uwind_speed' => array(
           'name'    => 'Wind speed (m/s)'
          ,'fmt'     => "%0.1f"
          ,'f'       => function($val,$a,$t) {
            return sqrt(pow($a['uwind_speed']['v'][$t],2) + pow($a['vwind_speed']['v'][$t],2));
          }
        )
        ,'vwind_speed' => array(
           'name' => 'Wind direction'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            $v = rad2deg(atan2($a['uwind_speed']['v'][$t],$a['vwind_speed']['v'][$t])) + 180 - 180;
            $v += $v < 0 ? 360 : 0;
            return $v % 360;
          }
        )
      )
    )
    ,array(
      'u'     => sprintf(
        "http://webserver.smast.umassd.edu:8000/wms/NecofsWave?ELEVATION=1&LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
        ,'hs'
        ,'facets_average_jet_0_0.5_node_False'
        ,$srs,$bbox,$x,$y,$w,$h
        ,'hs'
      )
      ,'fmt'  => 'csv'
      ,'vars' => array(
        'hs' => array(
           'name' => 'Wave height (m)'
          ,'fmt'  => "%0.1f"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
    )
    ,array(
      'u'     =>  sprintf(
        "http://coastmap.com/ecop/wms.aspx?LAYERS=%s&FORMAT=image/png&TRANSPARENT=TRUE&STYLES=%s&SERVICE=WMS&VERSION=1.1.1&REQUEST=GetFeatureInfo&SRS=%s&EXCEPTIONS=application/vnd.ogc.se_xml&BBOX=%s&X=%d&Y=%d&INFO_FORMAT=text/xml&WIDTH=%d&HEIGHT=%d&QUERY_LAYERS=%s&TIME="
        ,'NAM_WINDS'
        ,''
        ,$srs,$bbox,$x,$y,$w,$h
        ,'NAM_WINDS'
      )
      ,'fmt'  => 'xml'
      ,'vars' => array(
        'Wind Velocity' => array(
           'name' => 'Wind speed NAM (kts)'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
        ,'Direction'     => array(
           'name' => 'Wind direction NAM'
          ,'fmt'  => "%d"
          ,'f'    => function($val,$a,$t) {
            return $val;
          }
        )
      )
    )
  );

  $mapLayersStoreDataJS = "[
     ['wms','NERACOOS currents (surface)','http://webserver.smast.umassd.edu:8000/wms/GOM3?ELEVATION=1','u,v','vectors_average_jet_0_1.5_cell_True','image/png',1,true]
    ,['wms','NERACOOS salinity (surface)','http://webserver.smast.umassd.edu:8000/wms/GOM3?ELEVATION=1','salinity','facets_average_jet_0_32_node_False','image/png',1,false]
    ,['wms','NERACOOS water temp (surface)','http://webserver.smast.umassd.edu:8000/wms/GOM3?ELEVATION=1','temp','facets_average_jet_0_32_node_False','image/png',1,false]
    ,['wms','NERACOOS wave height (significant)','http://webserver.smast.umassd.edu:8000/wms/NecofsWave?ELEVATION=1','hs','facets_average_jet_0_0.5_node_False','image/png',1,false]
    ,['wms','NERACOOS winds (surface)','http://webserver.smast.umassd.edu:8000/wms/NecofsWave?ELEVATION=1','uwind_speed,vwind_speed','barbs_average_jet_2_10_cell_False,barbs_average_jet_2_10_cell_False','image/png',1,false]
    ,['wms','Winds (NAM)'                       ,'http://coastmap.com/ecop/wms.aspx','NAM_WINDS'           ,'WINDS_VERY_SPARSE_GRADIENT-False-1-0-45-Low','image/png',0.5,false]
  ]
";
  $weatherMapsStoreDataJS = "[
     ['NERACOOS currents (surface)',['NERACOOS currents (surface)'],['NERACOOS currents (surface)'],'Currents rule the day.',true,true]
    ,['NERACOOS salinity (surface)',['NERACOOS salinity (surface)'],['NERACOOS salinity (surface)'],'Currents rule the day.',true,false]
    ,['NERACOOS water temp (surface)',['NERACOOS water temp (surface)'],['NERACOOS water temp (surface)'],'Currents rule the day.',true,false]
    ,['NERACOOS wave height (significant)',['NERACOOS wave height (significant)'],['NERACOOS wave height (significant)'],'Currents rule the day.',true,false]
    ,['NERACOOS winds (surface)',['NERACOOS winds (surface)'],['NERACOOS winds (surface)'],'Currents rule the day.',true,false]
    ,['Winds (NAM)'                  ,['Winds (NAM)']                  ,['Winds (NAM)']            ,'Winds and waves rule the day.'              ,false,false]
  ]
";
?>
