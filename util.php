<?php
  function contains($bbox,$lon,$lat) {
    if ($bbox[0] <= $lon && $bbox[1] <= $lat && $lon <= $bbox[2] && $lat <= $bbox[3]) {
      return true;
    }
    return false;
  }

  function convertUnits($val,$uom,$toEnglish) {
    $english_category = array(
       'C'      => 'temperature'
      ,'deg C'  => 'temperature'
      ,'degC'   => 'temperature'
      ,'Cel'    => 'temperature'
      ,'Celsius' => 'temperature'
      ,'F'      => 'temperature'
      ,'deg F'  => 'temperature'
      ,'kelvin' => 'temperature'
      ,'m/s'    => 'velocity'
      ,'m.s-1'  => 'velocity'
      ,'cm/s'   => 'velocity'
      ,'kt'     => 'velocity'
      ,'m'      => 'elevation'
      ,'meters' => 'elevation'
      ,'mm'     => 'elevation'
      ,'bar'    => 'pressure'
      ,'1.0E-9one' => 'pressure'
    );
    if ($val === '') {
      return Array(Array('val' => '','uom' => '','cat' => ''));
    }
    $a = Array();
    if ($toEnglish) {
      if ($uom == 'm/s' || $uom == 'm.s-1') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 1.943844),'uom' => 'knots','cat' => $english_category[$uom]));
        array_push($a,Array('val' => sprintf("%.02f",$val * 2.23693629),'uom' => 'mph','cat' => $english_category[$uom]));
      }
      else if ($uom == 'cm/s') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 0.01943844),'uom' => 'knots','cat' => $english_category[$uom]));
        array_push($a,Array('val' => sprintf("%.02f",$val * 0.0223693629),'uom' => 'mph','cat' => $english_category[$uom]));
      }
      else if ($uom == 'm' || $uom == 'meters') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 3.2808399),'uom' => 'ft','cat' => $english_category[$uom]));
      }
      else if ($uom == 'm below land surface') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 3.2808399),'uom' => 'ft below land surface','cat' => $english_category[$uom]));
      }
      else if ($uom == 'mm') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 0.0393701),'uom' => 'in','cat' => $english_category[$uom]));
      }
      else if ($uom == 'C' || $uom == 'deg C' || $uom == 'Cel' || $uom == 'degC' || $uom == 'Celsius') {
        array_push($a,Array('val' => sprintf("%.02f",9/5*$val + 32),'uom' => 'F','cat' => $english_category[$uom]));
      }
      else if ($uom == 'mm') {
        array_push($a,Array('val' => sprintf("%.02f",$val * 0.0393700787),'uom' => 'in','cat' => $english_category[$uom]));
      }
      else if ($uom == 'kelvin') {
        array_push($a,Array('val' => sprintf("%.02f",($val - 272.15) * 9 / 5 + 32),'uom' => 'F','cat' => $english_category[$uom]));
      }
      else if ($uom == 'bar') {
        array_push($a,Array('val' => sprintf("%.02f",$val / 1000),'uom' => 'hPa','cat' => $english_category[$uom]));
      }
      else if ($uom == '1.0E-9one') {
        array_push($a,Array('val' => sprintf("%.01f",$val),'uom' => 'hPa','cat' => $english_category[$uom]));
      }
      else if ($uom == 'degT') {
        array_push($a,Array('val' => $val,'uom' => 'deg','cat' => ''));
      }
      else {
        return Array(Array('val' => $val,'uom' => $uom,'cat' => array_key_exists($uom,$english_category) ? $english_category[$uom] : ''));
      }
    }
    else {
      return Array(Array('val' => $val,'uom' => $uom,'cat' => $english_category[$uom]));
    }
    return $a;
  }
?>
