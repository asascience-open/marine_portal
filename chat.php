<?php
  $dbUser = getenv('dbUser');
  $dbPass = getenv('dbPass');
  $dbName = getenv('dbName');
  $dbPort = getenv('dbPort');

  $dbconn = pg_connect("host=localhost dbname=$dbName user=$dbUser password=$dbPass port=$dbPort");
  $maxCommentsPerConversation = 2 + (isset($_REQUEST['getConversation']) ? 100000000 : 0);
  $data = array('rows' => array());

  $err = false;
  $chat_q_sql = "select chat_q.seq,chat_q.text,max(coalesce(chat_a.t,chat_q.t)) as t,chat_u.id,to_char(chat_q.t at time zone 'UTC','YYYY-MM-DD HH24:MI') from chat_q left join chat_a on chat_a.chat_q = chat_q.seq,chat_u where ".(isset($_REQUEST['getConversation']) ? 'chat_q.seq = '.$_REQUEST['getConversation'] : 'true')." and chat_q.chat_u = chat_u.seq group by chat_q.seq,chat_q.text,chat_u.id,to_char(chat_q.t at time zone 'UTC','YYYY-MM-DD HH24:MI') order by chat_q.seq";
  if (isset($_REQUEST['newConversation'])) {
    $res = pg_query("insert into chat_q(text,chat_u) select '".str_replace("\\'","''",addslashes($_REQUEST['newConversation']))."',seq from chat_u where id = '".$_REQUEST['user']."'");
    $err = pg_last_error($dbconn);
  }
  else if (isset($_REQUEST['newComment'])) {
    pg_query("insert into chat_a (chat_q,text,chat_u) select ".$_REQUEST['newComment'].",'".str_replace("\\'","''",addslashes($_REQUEST['text']))."',chat_u.seq from chat_u where id = '".$_REQUEST['user']."'");
  }
  else if (isset($_REQUEST['search'])) {
    $id = array();
    $res = pg_query("select seq from (select chat_q.seq as seq,chat_q.text as q,chat_a.text as a from chat_q left join chat_a on chat_a.chat_q = chat_q.seq) as foo where to_tsvector(q) @@ to_tsquery('".addslashes($_REQUEST['search'])."') or to_tsvector(a) @@ to_tsquery('".addslashes($_REQUEST['search'])."')");
    while ($lin = pg_fetch_array($res)) {
      array_push($id,$lin[0]); 
    }
    $chat_q_sql = "select chat_q.seq,chat_q.text,max(coalesce(chat_a.t,chat_q.t)) as t,chat_u.id,to_char(chat_q.t at time zone 'UTC','YYYY-MM-DD HH24:MI') from chat_q left join chat_a on chat_a.chat_q = chat_q.seq,chat_u where chat_q.seq in (".implode(',',$id).") and chat_q.chat_u = chat_u.seq group by chat_q.seq,chat_q.text,chat_u.id,to_char(chat_q.t at time zone 'UTC','YYYY-MM-DD HH24:MI') order by t desc";
  }

  $chat = array();
  $i = 0;
  $res0 = pg_query($chat_q_sql);
  while ($lin0 = pg_fetch_array($res0)) {
    array_push($data['rows'],array(
       'id'          => $i++
      ,'title'       => $lin0[1]
      ,'description' => "<table><tr><td style='width:20px'><a href='javascript:addComment($lin0[0],".(isset($_REQUEST['getConversation']) ? $_REQUEST['getConversation'] : 'false').")'><img src='img/comment_add16.png'></a></td><td><a href='javascript:addComment($lin0[0],".(isset($_REQUEST['getConversation']) ? $_REQUEST['getConversation'] : 'false').")'>Add a comment</a> to this conversation.</td></tr></table>"
      ,'date'        => ''
      ,'user'        => $lin0[3] != 'false' ? $lin0[3] : 'anonymous'
      ,'err'         => $err
      ,'singleC'     => isset($_REQUEST['getConversation'])
    ));
    $res1 = pg_query("select chat_a.seq,text,to_char(t at time zone 'UTC','YYYY-MM-DD HH24:MI'),id from chat_a,chat_u where chat_q = $lin0[0] and chat_a.chat_u = chat_u.seq order by t desc");
    $hits = 0;
    while ($lin1 = pg_fetch_array($res1)) {
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
/*
    array_push($data['rows'],array(
       'id'          => $i++
      ,'title'       => $lin0[1]
      ,'description' => '<table><tr><td style="width:20px"><img src="img/user16.png"></td><td>'.$lin0[1].'</td></tr></table>'
      ,'date'        => $lin0[4] 
      ,'user'        => $lin0[3] != 'false' ? $lin0[3] : 'anonymous'
      ,'err'         => $err
      ,'singleC'     => isset($_REQUEST['getConversation'])
    ));
*/
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
  pg_close($dbconn);
  echo json_encode($data);
?>
