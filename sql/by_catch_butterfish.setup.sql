drop table by_catch_butterfish cascade;
create table by_catch_butterfish (
   seq               serial unique
  ,batch_update_date timestamp default now()
  ,val               varchar
  ,the_geom          geometry
);
create index by_catch_butterfish__batch_update_date on by_catch_butterfish(batch_update_date);
create index by_catch_butterfish__the_geom on by_catch_butterfish using gist(the_geom);

create view by_catch_butterfish_latest as
select
  *
from
  by_catch_butterfish
where
  batch_update_date in (select max(batch_update_date) from by_catch_butterfish);
