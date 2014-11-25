insert into chat_u(id) values ('false'); -- for anonymous entries

insert into chat_q(txt,chat_u) select
   'Are there other data sources or types that you know of that could be integrated into the website?'
  ,seq
from
  chat_u
where
  id = 'false';

insert into chat_q(txt,chat_u) select
   'Do you have suggestions for improving website navigation and usability?'
  ,seq
from
  chat_u
where
  id = 'false';

insert into chat_q(txt,chat_u) select
   'How/why would you use this website?'
  ,seq
from
  chat_u
where
  id = 'false';

insert into chat_q(txt,chat_u) select
   'Other general comments/questions'
  ,seq
from
  chat_u
where
  id = 'false';
