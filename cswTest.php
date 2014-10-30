<?php
  set_time_limit(120);

  header ("Content-Type:text/xml");
  $post = <<<EOP
<?xml version="1.0" encoding="UTF-8"?>
<csw:GetRecords xmlns:csw="http://www.opengis.net/cat/csw/2.0.2" service="CSW" version="2.0.2" resultType="results">
  <csw:Query typeNames="csw:Record">
    <csw:ElementSetName>full</csw:ElementSetName>
    <csw:Constraint version="1.1.0">
      <Filter xmlns="http://www.opengis.net/ogc" xmlns:gml="http://www.opengis.net/gml">
        <PropertyIsLike wildCard="%" singleChar="_" escape="\\">
          <PropertyName>subject</PropertyName>
          <Literal>GLOS_CATEGORY:$_REQUEST[category]</Literal>
        </PropertyIsLike>
      </Filter>
    </csw:Constraint>
  </csw:Query>
</csw:GetRecords>
EOP;

  echo file_get_contents(wget('http://64.9.200.112/geonetwork/srv/eng/csw',$post));

  function wget($u,$p) {
    $request  = '/tmp/'.rand().time().'.post.xml';
    $response = '/tmp/'.rand().time().'.xml';
    file_put_contents($request,$p);
    `wget --header "Content-Type:text/xml" --post-file=$request '$u' -O $response > /dev/null 2>&1`;
    return $response;
  }
?>
