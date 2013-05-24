<?php
  header("Content-type: application/csv");
  header("Content-Disposition: attachment; filename=\"".sprintf("%s-%s",$_REQUEST['site'],$_REQUEST['var']).".csv\"");
  header("Pragma: no-cache");
  header("Expires: 0");
  echo file_get_contents(sprintf("/%s/%s/%s.csv",getenv('tmpFs'),$_REQUEST['dir'],$_REQUEST['csv']));
?>
