<?php
  $data = json_decode($_REQUEST['data']);
  $profile = json_decode($_REQUEST['profile']);

  $hdr = array(sprintf("Time (%s)",$_REQUEST['tz']));
  if (is_array($profile)) {
    for ($i = 0; $i < count($profile); $i++) {
      array_push($hdr,sprintf("\"%s (%s)\"",str_replace('"','',$profile[$i]),str_replace('"','',$_REQUEST['uom'])));
    }
  }
  else {
    array_push($hdr,sprintf("\"%s (%s)\"",str_replace('"','',$_REQUEST['var']),str_replace('"','',$_REQUEST['uom'])));
  }
  $csv = array(implode(',',$hdr));

  for ($i = 0; $i < count($data); $i++) {
    for ($j = 0; $j < count($data[$i]); $j++) {
      if (strpos($data[$i][$j],',')) {
        $data[$i][$j] = '"'.$data[$i][$j].'"';
      }
    }
    array_push($csv,implode(',',$data[$i]));
  }

  $safe_sess = preg_replace("/[^a-zA-Z0-9\s]/",'',$_REQUEST['sess']);
  $safe_file = preg_replace("/[^a-zA-Z0-9\s]/",'',$_REQUEST['id']);

  mkdir(sprintf("/%s/%s",getenv('tmpFs'),$_REQUEST['sess']));
  file_put_contents(sprintf("/%s/%s/%s.csv",getenv('tmpFs'),$safe_sess,$safe_file),implode("\n",$csv)."\n");
  echo json_encode(array(
     'dir'  => $safe_sess
    ,'csv'  => $safe_file
    ,'site' => json_decode($_REQUEST['site'])
    ,'var'  => json_decode($_REQUEST['var'])
  ));
?>
