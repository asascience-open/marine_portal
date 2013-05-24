drop table by_catch_scallop_yellowtail_nantucket_grid cascade;
create table by_catch_scallop_yellowtail_nantucket_grid (
   seq               serial unique
  ,batch_update_date timestamp default now()
  ,val               float
  ,the_geom          geometry
);
create index by_catch_scallop_yellowtail_nantucket_grid__batch_update_date on by_catch_scallop_yellowtail_nantucket_grid(batch_update_date);
create index by_catch_scallop_yellowtail_nantucket_grid__the_geom on by_catch_scallop_yellowtail_nantucket_grid using gist(the_geom);

create view by_catch_scallop_yellowtail_nantucket_grid_latest as
select
  *
from
  by_catch_scallop_yellowtail_nantucket_grid
where
  batch_update_date in (select max(batch_update_date) from by_catch_scallop_yellowtail_nantucket_grid);
