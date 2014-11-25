<?php
  $qs = $_SERVER['QUERY_STRING'];
  $f  = getenv('icons').'/'.md5($qs).'.png';
  if (!file_exists($f) || filesize($f) < 0) {
    $bin = file_get_contents("http://mymaracoos.org/cgi-bin/obs2?$qs");
    file_put_contents($f,$bin);
  }
  header('Content-Type: image/png');
  echo file_get_contents($f);
?>
