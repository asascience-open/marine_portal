drop table by_catch_scallop_yellowtail_area1 cascade;
create table by_catch_scallop_yellowtail_area1 (
   seq               serial unique
  ,batch_update_date timestamp default now()
  ,val               float
  ,the_geom          geometry
);
create index by_catch_scallop_yellowtail_area1__batch_update_date on by_catch_scallop_yellowtail_area1(batch_update_date);
create index by_catch_scallop_yellowtail_area1__the_geom on by_catch_scallop_yellowtail_area1 using gist(the_geom);

create view by_catch_scallop_yellowtail_area1_latest as
select
  *
from
  by_catch_scallop_yellowtail_area1
where
  batch_update_date in (select max(batch_update_date) from by_catch_scallop_yellowtail_area1);
