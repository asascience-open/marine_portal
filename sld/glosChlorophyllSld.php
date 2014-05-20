<?php
  $sld = file_get_contents(
     'glosChlorophyll'
    .$_REQUEST['REGION']
    .(isset($_REQUEST['LEGENDONLY']) ? 'LegendOnly' : '')
    .'.xml'
  );
  $sld = str_replace('LAYER',$_REQUEST['LAYER'],$sld);
  header("Content-type: text/xml; charset=utf-8");
  echo $sld;
?>
