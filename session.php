<?php
  $dbconn = new PDO('sqlite:db/chat.sqlite3');

  $err = '';
 
  $userId    = ''; 
  $permalink = $_REQUEST['permalink'];
  if (isset($_REQUEST['userId']) && isset($_REQUEST['permalink'])) {
    $dbconn->query("update chat_u set permalink = '$permalink' where id = '".$_REQUEST['userId']."'");
  }
  else {
    $res = $dbconn->query("insert into chat_u(id) values ('".$_REQUEST['userId']."')");
    if (!$res) {
      $err = $dbconn->errorInfo();
    }
    $res = $dbconn->query("select permalink from chat_u where id = '".$_REQUEST['userId']."'");
    foreach ($res as $lin) {
      $permalink = $lin[0];
      $err = '';
    }
  }

  echo json_encode(array('err' => $err,'userId' => $_REQUEST['userId'],'permalink' => $permalink));
?>
