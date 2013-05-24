drop table by_catch_river_herring_bottom_trawl_rhode_island cascade;
create table by_catch_river_herring_bottom_trawl_rhode_island (
   seq               serial unique
  ,batch_update_date timestamp default now()
  ,val               float
  ,the_geom          geometry
);
create index by_catch_river_herring_bottom_trawl_rhode_island__batch_update_date on by_catch_river_herring_bottom_trawl_rhode_island(batch_update_date);
create index by_catch_river_herring_bottom_trawl_rhode_island__the_geom on by_catch_river_herring_bottom_trawl_rhode_island using gist(the_geom);

create view by_catch_river_herring_bottom_trawl_rhode_island_latest as
select
  *
from
  by_catch_river_herring_bottom_trawl_rhode_island
where
  batch_update_date in (select max(batch_update_date) from by_catch_river_herring_bottom_trawl_rhode_island);
