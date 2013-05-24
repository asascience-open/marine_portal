<?php
  $dbUser = getenv('dbUser');
  $dbPass = getenv('dbPass');
  $dbName = getenv('dbName');
  $dbPort = getenv('dbPort');
  $dbconn = pg_connect("host=localhost dbname=$dbName user=$dbUser password=$dbPass port=$dbPort");

  $err = '';
 
  $userId    = ''; 
  $permalink = $_REQUEST['permalink'];
  if (isset($_REQUEST['userId']) && isset($_REQUEST['permalink'])) {
    pg_query("update chat_u set permalink = '$permalink' where id = '".$_REQUEST['userId']."'");
  }
  else {
    $res = pg_query("insert into chat_u(id) values ('".$_REQUEST['userId']."')");
    $err = pg_last_error($dbconn);
    $res = pg_query("select permalink from chat_u where id = '".$_REQUEST['userId']."'");
    while ($lin = pg_fetch_array($res)) { 
      $permalink = $lin[0];
    }
  }

  pg_close($dbconn);
  echo json_encode(array(err => $err,userId => $_REQUEST['userId'],permalink => $permalink));
?>
