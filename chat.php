<?php
  $dbconn = new PDO('sqlite:db/chat.sqlite3');
  $maxCommentsPerConversation = 2 + (isset($_REQUEST['getConversation']) ? 100000000 : 0);
  $data = array('rows' => array());

  $err = false;
  $chat_q_sql = "select chat_q.seq,chat_q.txt,max(coalesce(chat_a.t,chat_q.t)) as t,chat_u.id,chat_q.t from chat_q left join chat_a on chat_a.chat_q = chat_q.seq,chat_u where ".(isset($_REQUEST['getConversation']) ? 'chat_q.seq = '.$_REQUEST['getConversation'] : '1')." and chat_q.chat_u = chat_u.seq group by chat_q.seq,chat_q.txt,chat_u.id,chat_q.t order by chat_q.seq";
  if (isset($_REQUEST['newConversation'])) {
    $res = $dbconn->query("insert into chat_q(txt,chat_u) select '".str_replace("\\'","''",addslashes($_REQUEST['newConversation']))."',seq from chat_u where id = '".$_REQUEST['user']."'");
    $err = $dbconn->errorInfo();
  }
  else if (isset($_REQUEST['newComment'])) {
    $dbconn->query("insert into chat_a (chat_q,txt,chat_u) select ".$_REQUEST['newComment'].",'".str_replace("\\'","''",addslashes($_REQUEST['text']))."',chat_u.seq from chat_u where id = '".$_REQUEST['user']."'");
  }

  $chat = array();
  $i = 0;
  $res0 = $dbconn->query($chat_q_sql);
  foreach ($res0 as $lin0) {
    array_push($data['rows'],array(
       'id'          => $i++
      ,'title'       => $lin0[1]
      ,'description' => "<table><tr><td style='width:20px'><a href='javascript:addComment($lin0[0],".(isset($_REQUEST['getConversation']) ? $_REQUEST['getConversation'] : 'false').")'><img src='img/comment_add16.png'></a></td><td><a href='javascript:addComment($lin0[0],".(isset($_REQUEST['getConversation']) ? $_REQUEST['getConversation'] : 'false').")'>Add a comment</a> to this conversation.</td></tr></table>"
      ,'date'        => ''
      ,'user'        => $lin0[3] != 'false' ? $lin0[3] : 'anonymous'
      ,'err'         => $err
      ,'singleC'     => isset($_REQUEST['getConversation'])
    ));
    $res1 = $dbconn->query("select chat_a.seq,txt,t,id from chat_a,chat_u where chat_q = $lin0[0] and chat_a.chat_u = chat_u.seq order by t desc");
    $hits = 0;
    foreach ($res1 as $lin1) {
      if ($hits < $maxCommentsPerConversation) {
        array_push($data['rows'],array(
           'id'          => $i++
          ,'title'       => $lin0[1]
          ,'description' => '<table><tr><td style="width:20px"><img src="img/user16.png"></td><td>'.$lin1[1].'</td></tr></table>'
          ,'date'        => $lin1[2]
          ,'user'        => $lin1[3] != 'false' ? $lin1[3] : 'anonymous'
          ,'err'         => $err
          ,'singleC'     => isset($_REQUEST['getConversation'])
        ));
      }
      $hits++;
    }
    if ($hits > $maxCommentsPerConversation) {
      array_push($data['rows'],array(
         'id'          => $i++
        ,'title'       => $lin0[1]
        ,'description' => "<table><tr><td style='width:20px'><a href='javascript:viewConversation($lin0[0])'><img src='img/comments16.png'></a></td><td><a href='javascript:viewConversation($lin0[0])'>View all comments</a> from this conversation.  Only the most recent comments are being shown.</td></tr></table>"
        ,'date'        => ''
        ,'user'        => $lin0[3] != 'false' ? $lin0[3] : 'anonymous'
        ,'err'         => $err
        ,'singleC'     => isset($_REQUEST['getConversation'])
      ));
    }
  }

  if (isset($_REQUEST['start'])) {
    $data['rows'] = array_slice($data['rows'],$_REQUEST['start']);
  }
  $data['rows'] = array_slice($data['rows'],0,$_REQUEST['limit']);

  $data['results'] = $i;
  echo json_encode($data);
?>
