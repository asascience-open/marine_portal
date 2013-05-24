<?php
  $data = json_decode($_REQUEST['data']);

  $csv = array(
    sprintf("Time (%s),\"%s (%s)\"",$_REQUEST['tz'],str_replace('"','',$_REQUEST['var']),str_replace('"','',$_REQUEST['uom']))
  );

  for ($i = 0; $i < count($data); $i++) {
    if (strpos($data[$i][0],',')) {
      $data[$i][0] = '"'.$data[$i][0].'"';
    }
    if (strpos($data[$i][1],',')) {
      $data[$i][1] = '"'.$data[$i][1].'"';
    }
    array_push($csv,sprintf("%s,%s",$data[$i][0],$data[$i][1]));
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
