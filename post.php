<?php
  set_time_limit(120);

  header ("Content-Type:text/xml");
  $r = file_get_contents(wget($_REQUEST['url'],file_get_contents('php://input')));
file_put_contents('/tmp/maplog',$r);

  // DomQuery doesn't support namespaces -- BOO!
  // so swap out the : after each one we care about with a _
  //
  //   e.g. the ns param might be something like 'srv|gmd|gco'

  echo preg_replace('/('.$_REQUEST['ns'].'):/','\1_',$r);

  function wget($u,$p) {
    $request  = '/tmp/'.rand().time().'.post.xml';
    $response = '/tmp/'.rand().time().'.xml';
    file_put_contents($request,$p);
    `wget --header "Content-Type:text/xml" --post-file=$request '$u' -O $response > /dev/null 2>&1`;
    return $response;
  }
?>
