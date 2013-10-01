<?php
  session_start();
  include_once('config/'.getenv('config').'.php');
  $version = 0.38;
?>
<html>
  <head>
    <title><?php echo $title?></title>
    <link rel="stylesheet" type="text/css" href="http://cloud.iboattrack.com/js/ext-3.4.1/resources/css/ext-all.css"/>
    <link rel="stylesheet" type="text/css" href="style.css?<?php echo $version?>"/>
    <!--[if IE]>
      <link rel="stylesheet" type="text/css" href="style.ie.css?<?php echo $version?>" />
    <![endif]-->
    <link rel="stylesheet" type="text/css" href="css/<?php echo getenv('config')?>.css?<?php echo $version?>"/>
    <!--[if IE]>
      <link rel="stylesheet" type="text/css" href="css/<?php echo getenv('config')?>.ie.css?<?php echo $version?>"/>
    <![endif]-->
    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', '<?php echo $googleId?>']);
      _gaq.push(['_trackPageview']);
      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
  </head>
  <body onload="Ext.onReady(function(){init()})">
    <div id="loading-mask"></div>
    <div id="loading">
      <span id="loading-message">Loading core API. Please wait...</span>
    </div>
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="http://cloud.iboattrack.com/js/ext-3.4.1/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="http://cloud.iboattrack.com/js/ext-3.4.1/ext-all.js"></script>
    <script type="text/javascript" src="./js/ext-3.3.0/SearchField.js"></script>
    <script type="text/javascript" src="./js/OpenLayers-2.12-rc7/OpenLayers-closure-ie10-fix.js"></script>
    <link rel="stylesheet" type="text/css" href="./js/OpenLayers-2.12-rc7/theme/default/google.css"/>
    <script type="text/javascript" src="./js/CloudMade.js"></script>
    <script>
      var userId    = <?php echo json_encode(isset($_REQUEST['userId']) ? $_REQUEST['userId'] : false)?>;
      var baseUrl   = String('<?php echo $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']?>').split('/');
      baseUrl.pop();
      baseUrl       = 'http://' + baseUrl.join('/') + '/';
      var sessionId = '<?php echo session_id()?>';
      var center          = '<?php echo $mapCenter?>'.split(',');
      var zoom            = '<?php echo $mapZoom?>'.split(',');
      var startupBookmark = <?php echo json_encode(isset($_REQUEST['mapCenter']))?>;
      var startupCenter   = '<?php echo isset($_REQUEST['mapCenter']) ? $_REQUEST['mapCenter'] : $mapCenter?>'.split(',');
      var startupZoom     = '<?php echo isset($_REQUEST['mapZoom']) ? $_REQUEST['mapZoom'] : $mapZoom?>';
      var startupMode     = '<?php echo isset($_REQUEST['mode']) ? $_REQUEST['mode'] : $mode?>';
      var startupFCLayer    = <?php echo isset($_REQUEST['fcLayer']) ? json_encode($_REQUEST['fcLayer']) : 'false'?>;
      var startupFCContrast = <?php echo isset($_REQUEST['fcContrast']) ? json_encode($_REQUEST['fcContrast']) : 'false'?>;
      var banner    = {
         height : '<?php echo $bannerHeight?>'
        ,html   : '<?php echo $bannerHtml?>'
      };
      var forecastFooter    = <?php echo isset($forecastFooter) ? json_encode($forecastFooter) : json_encode(false)?>;
      var startupWXLayer    = <?php echo isset($_REQUEST['wxLayer']) ? json_encode($_REQUEST['wxLayer']) : 'false'?>;
      var startupWXContrast = <?php echo isset($_REQUEST['wxContrast']) ? json_encode($_REQUEST['wxContrast']) : 'false'?>;
      var startupWWA        = <?php echo isset($_REQUEST['wwa']) ? json_encode($_REQUEST['wwa'] == 'on') : json_encode($defaultWWA == 'on')?>;
      var startupBathyContours = <?php echo isset($_REQUEST['bathyContours']) ? json_encode($_REQUEST['bathyContours'] == 'on') : json_encode(false)?>;
      var startupbyCatchLayer = <?php echo isset($_REQUEST['byCatchLayer']) ? json_encode($_REQUEST['byCatchLayer']) : 'false'?>;
      var southPanel = {
         height    : '<?php echo $southPanelHeight?>'
        ,html      : '<?php echo $southPanelHtml?>'
      };
      var extraInitJS  = <?php echo json_encode($extraInitJS)?>;
      var viewer       = <?php echo json_encode(isset($_REQUEST['viewer']) ? $_REQUEST['viewer'] : $viewer)?>;
      var byCatch      = <?php echo json_encode($byCatch == 'on')?>;
      var chat         = <?php echo json_encode($chat == 'on' ? getenv('config') : false)?>;
      var search       = <?php echo json_encode($search == 'on')?>;
      var tbar         = <?php echo json_encode($tbar == 'on')?>;
      var weatherTab   = '<?php echo $weatherTab?>';
      var minZoom      = <?php echo json_encode($minZoom)?>;
      var obsCull      = <?php echo json_encode($obsCull)?>;
      var dataBbox     = '<?php echo $dataBbox?>'.split(',');
      var defaultObs   = '<?php echo isset($_REQUEST['obs']) ? $_REQUEST['obs'] : $defaultObs?>';
      var bathyContours = <?php echo json_encode($bathyContours == 'on')?>;
      var availableObs = {hits : 0};
      for (var i = 0; i < <?php echo json_encode($availableObs)?>.length; i++) {
        availableObs[<?php echo json_encode($availableObs)?>[i]] = true;
        availableObs.hits++;
       }
      var defaultFC             = '<?php echo $defaultFC?>';
      var defaultWWA            = '<?php echo $defaultWWA?>';
      var mapLayersStoreData    = <?php echo $mapLayersStoreDataJS?>;
      var forecastMapsStoreData = <?php echo $forecastMapsStoreDataJS?>;
      var weatherMapsStoreData  = <?php echo $weatherMapsStoreDataJS?>;
      var byCatchMapsStoreData  = <?php echo $byCatchMapsStoreDataJS?>;
      var defaultBasemap        = '<?php echo isset($_REQUEST['basemap']) ? $_REQUEST['basemap'] : $basemap?>';
      var fcSliderIncrement     = <?php echo $fcSliderIncrement?>;
      var geo                   = {bufferJSON : <?php echo file_get_contents($buffer)?>};
      var obsLegendsPath        = '<?php echo $obsLegendsPath?>';
      var obsCptRanges          = <?php echo json_encode($obsCptRanges)?>;
      var catalogQueryXML       = <?php echo $catalogQueryXML?>;
      var xsl                   = 'xsl/<?php echo getenv('config')?>.iso.xsl';
      var splashHtml            = <?php echo json_encode(@file_get_contents('splash/'.getenv('config').'.html'))?>;
      var splashTitle           = <?php echo json_encode($title)?>;
      var helpPage              = <?php echo json_encode(isset($hasHelp) ? 'help/'.getenv('config').'/' : false)?>;
    </script>
    <script type="text/javascript" src="./js/jquery/jquery.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.js?bg"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.time.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.crosshair.js"></script>
    <script type="text/javascript" src="./js/jquery/jquery.flot.navigate.js"></script>
    <!--[if IE]><script type="text/javascript" src="./js/jquery/excanvas.js"></script><![endif]-->
    <script type="text/javascript" src="./js/dateFormat.js"></script>
    <script type="text/javascript" src="map.js?<?php echo $version?>"></script>
  </body>
</html>
