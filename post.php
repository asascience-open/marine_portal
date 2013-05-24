<?php
  set_time_limit(120);

  $r = file_get_contents(
     $_REQUEST['url']
    ,false
    ,stream_context_create(array('http' => array(
       'method'  => 'POST'
      ,'header'  => 'Content-type: text/xml'
      ,'content' => file_get_contents('php://input')
    )))
  );

  // DomQuery doesn't support namespaces -- BOO!
  // so swap out the : after each one we care about with a _
  // 
  //   e.g. the ns param might be something like 'srv|gmd|gco'

  header ("Content-Type:text/xml");
  echo preg_replace('/('.$_REQUEST['ns'].'):/','\1_',$r);
?>
