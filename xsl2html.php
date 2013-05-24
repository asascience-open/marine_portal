<?php
  try {
    $xml = new SimpleXMLElement(@file_get_contents($_REQUEST['xsl']));
  }
  catch (Exception $e) {
    echo 'There was a problem accessing the XSL template.';
    exit;
  }
  $xslt = new XSLTProcessor();
  $xslt->importStylesheet($xml);

  try {
/*
    $xml = new SimpleXMLElement(@file_get_contents(
       $_REQUEST['url']
      ,false
      ,stream_context_create(array('http' => array(
         'method'  => 'POST'
        ,'header'  => 'Content-type: text/xml'
        ,'content' => str_replace('___ID___',$_REQUEST['id'],file_get_contents('xml/glosISOQuery.xml'))
      )))
    ));
*/
    $xml = new SimpleXMLElement(file_get_contents(wget($_REQUEST['url'],str_replace('___ID___',$_REQUEST['id'],file_get_contents('xml/glosISOQuery.xml')))));
  }
  catch (Exception $e) {
    echo 'There was a problem accessing the ISO record.';
    echo ' '.$e->getMessage();
    exit;
  }

  echo $xslt->transformToXml($xml);

  function wget($u,$p) {
    $request  = '/tmp/'.rand().time().'.post.xml';
    $response = '/tmp/'.rand().time().'.xml';
    file_put_contents($request,$p);
    `wget --header "Content-Type:text/xml" --post-file=$request '$u' -O $response > /dev/null 2>&1`;
    return $response;
  }
?>
