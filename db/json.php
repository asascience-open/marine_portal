<?php
  $db = new PDO('sqlite:json.sqlite3');
  $db->query('create table json(seq integer primary key,f text,providers text,ready integer)');
?>
