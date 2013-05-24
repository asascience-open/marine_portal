drop table chat_a;
drop table chat_q;
drop table chat_u;

create table chat_u (
   seq         serial unique
  ,id          varchar unique
  ,permalink   varchar
);
create table chat_q (
   seq         serial unique
  ,text        varchar unique
  ,t           timestamp with time zone default now()
  ,chat_u      integer references chat_u(seq)
);
create table chat_a (
   seq         serial unique
  ,chat_q      integer references chat_q(seq)
  ,text        varchar
  ,t           timestamp with time zone default now()
  ,chat_u      integer references chat_u(seq)
);

insert into chat_u(id) values ('false'); -- for anonymous entries

insert into chat_q(text,chat_u) select
   'Are there other data sources or types that you know of that could be integrated into the website?'
  ,seq
from
  chat_u
where
  id = 'false';

insert into chat_q(text,chat_u) select
   'Do you have suggestions for improving website navigation and usability?'
  ,seq
from
  chat_u
where
  id = 'false';

insert into chat_q(text,chat_u) select
   'How/why would you use this website?'
  ,seq
from
  chat_u
where
  id = 'false';

insert into chat_q(text,chat_u) select
   'Other general comments/questions'
  ,seq
from
  chat_u
where
  id = 'false';

insert into chat_a (chat_q,text,t,chat_u) select
   chat_q.seq
  ,'aliquet. Praesent gravida massa nec nunc volutpat a, volutpat massa in enim. Phasellus lorem lorem velit pede bibendum eget, rutrum euismod, nulla ut nunc tempus ultrices, dui at nulla. Phasellus lacinia eget, bibendum tellus, fringilla mollis. Suspendisse a adipiscing elit. Quisque lobortis, massa id nibh. Quisque facilisis felis, ullamcorper varius nec, velit. Vivamus arcu magna, fermentum orci vitae fermentum leo vel risus. Sed metus in dolor. Mauris in dictum eget, molestie tristique mauris viverra eget, congue augue sit amet cursus quis, lacinia neque. Cras elementum. Morbi et malesuada fames ac turpis in magna et turpis. Donec libero quis neque. In'
  ,now()
  ,chat_u.seq
from
   chat_q
  ,chat_u
where
  text = 'How old are you?'
  and id = 'charlton@2creek.com'
  and false;
