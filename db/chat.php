<?php
  $db = new PDO('sqlite:chat.sqlite3');
  $db->query('create table chat_u(seq integer primary key,id text unique,permalink text)');
  $db->query('create table chat_q(seq integer primary key,txt text unique,t timestamp default current_timestamp,chat_u integer,foreign key(chat_u) references chat_u(seq))');
  $db->query('create table chat_a(seq integer primary key,txt text unique,t timestamp default current_timestamp,chat_u integer,chat_q integer,foreign key(chat_u) references chat_u(seq),foreign key(chat_q) references chat_q(seq))');
?>
