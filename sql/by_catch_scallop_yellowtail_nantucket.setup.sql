drop table by_catch_scallop_yellowtail_nantucket cascade;
create table by_catch_scallop_yellowtail_nantucket (
   seq               serial unique
  ,batch_update_date timestamp default now()
  ,val               float
  ,the_geom          geometry
);
create index by_catch_scallop_yellowtail_nantucket__batch_update_date on by_catch_scallop_yellowtail_nantucket(batch_update_date);
create index by_catch_scallop_yellowtail_nantucket__the_geom on by_catch_scallop_yellowtail_nantucket using gist(the_geom);

create view by_catch_scallop_yellowtail_nantucket_latest as
select
  *
from
  by_catch_scallop_yellowtail_nantucket
where
  batch_update_date in (select max(batch_update_date) from by_catch_scallop_yellowtail_nantucket);
