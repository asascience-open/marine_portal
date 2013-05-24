drop table by_catch_river_herring_midwater_trawl_capecod cascade;
create table by_catch_river_herring_midwater_trawl_capecod (
   seq               serial unique
  ,batch_update_date timestamp default now()
  ,val               float
  ,the_geom          geometry
);
create index by_catch_river_herring_midwater_trawl_capecod__batch_update_date on by_catch_river_herring_midwater_trawl_capecod(batch_update_date);
create index by_catch_river_herring_midwater_trawl_capecod__the_geom on by_catch_river_herring_midwater_trawl_capecod using gist(the_geom);

create view by_catch_river_herring_midwater_trawl_capecod_latest as
select
  *
from
  by_catch_river_herring_midwater_trawl_capecod
where
  batch_update_date in (select max(batch_update_date) from by_catch_river_herring_midwater_trawl_capecod);
